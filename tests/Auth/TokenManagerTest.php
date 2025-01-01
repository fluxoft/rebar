<?php

namespace Fluxoft\Rebar\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Fluxoft\Rebar\Auth\Exceptions\InvalidTokenException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TokenManagerTest extends TestCase {
	private $refreshTokenMapperMock;
	private $claimsProviderMock;
	private $tokenManager;

	protected function setUp(): void {
		/** @var RefreshTokenMapperInterface|MockObject $refreshTokenMapperMock */
		$this->refreshTokenMapperMock = $this->createMock(RefreshTokenMapperInterface::class);
		/** @var ClaimsProviderInterface|MockObject $claimsProviderMock */
		$this->claimsProviderMock = $this->createMock(ClaimsProviderInterface::class);

		$this->tokenManager = new TokenManager(
			$this->refreshTokenMapperMock,
			$this->claimsProviderMock,
			'secretKey'
		);
	}

	public function testGenerateAccessToken() {
		$userMock = $this->createMock(UserInterface::class);
		$this->claimsProviderMock
			->method('GetClaims')
			->with($userMock)
			->willReturn(['userId' => 123]);

		$accessToken = $this->tokenManager->GenerateAccessToken($userMock);

		$this->assertIsString($accessToken, 'Expected access token to be a string.');

		// Decode the JWT to verify its payload
		$payload = (array) JWT::decode($accessToken, new Key('secretKey', 'HS256'));
		$this->assertEquals(123, $payload['userId'], 'Expected userId claim to match.');
	}

	public function testGenerateRefreshToken() {
		$userMock = $this->createMock(UserInterface::class);
		$userMock->method('GetId')->willReturn(123);

		$refreshToken = $this->tokenManager->GenerateRefreshToken($userMock);

		$this->assertIsString($refreshToken, 'Expected refresh token to be a string.');

		// Decode and verify the format of the refresh token
		$parts = explode('|', base64_decode($refreshToken));
		$this->assertCount(3, $parts, 'Expected refresh token to have three parts.');
		$this->assertEquals(123, $parts[0], 'Expected userId to match in refresh token.');
	}

	public function testStoreRefreshToken() {
		$refreshToken = base64_encode('123|seriesId|token');

		$this->refreshTokenMapperMock
			->expects($this->once())
			->method('SaveRefreshToken')
			->with($this->callback(function ($refreshTokenObj) {
				return $refreshTokenObj->UserId === '123' &&
					$refreshTokenObj->SeriesId === 'seriesId' &&
					$refreshTokenObj->Token === 'token';
			}));

		$this->tokenManager->StoreRefreshToken($refreshToken);
	}

	public function testValidateRefreshToken() {
		$refreshToken = base64_encode('123|seriesId|token');

		$refreshTokenObj            = new RefreshToken();
		$refreshTokenObj->ExpiresAt = (new \DateTimeImmutable('+1 day'))->format('Y-m-d H:i:s');

		$this->refreshTokenMapperMock
			->method('GetRefreshToken')
			->willReturn($refreshTokenObj);

		$result = $this->tokenManager->ValidateRefreshToken($refreshToken);

		$this->assertTrue($result, 'Expected refresh token to be valid.');
	}

	public function testValidateRefreshTokenReturnsFalseWhenTokenNotFound() {
		$validTokenString = base64_encode('123|1|token1'); // Properly formatted

		$this->refreshTokenMapperMock
			->method('GetRefreshToken')
			->willReturn(null); // Simulate token not found

		$isValid = $this->tokenManager->ValidateRefreshToken($validTokenString);

		$this->assertFalse($isValid, 'Expected ValidateRefreshToken to return false when token is not found.');
	}

	public function testRevokeRefreshToken() {
		$refreshToken = base64_encode('123|seriesId|token');

		$refreshTokenObj = new RefreshToken();

		$this->refreshTokenMapperMock
			->method('GetRefreshToken')
			->willReturn($refreshTokenObj);

		$this->refreshTokenMapperMock
			->expects($this->once())
			->method('RevokeRefreshToken')
			->with($this->callback(function ($refreshTokenObj) {
				return isset($refreshTokenObj->RevokedAt);
			}));

		$this->tokenManager->RevokeRefreshToken($refreshToken);
	}

	public function testRevokeRefreshTokensByUserId() {
		$refreshTokens = [
			(new RefreshToken([
				'UserId' => '123',
				'SeriesId' => 123,
				'Token' => 'token1'
			])),
			(new RefreshToken([
				'UserId' => '123',
				'SeriesId' => 456,
				'Token' => 'token2'
			])),
		];

		$this->refreshTokenMapperMock
			->method('GetRefreshTokensByUserId')
			->with('123')
			->willReturn($refreshTokens);

		// Mock the TokenManager's RevokeRefreshToken to track its calls
		/** @var TokenManager|MockObject $tokenManagerMock */
		$tokenManagerMock = $this->getMockBuilder(TokenManager::class)
			->setConstructorArgs([
				$this->refreshTokenMapperMock,
				$this->claimsProviderMock,
				'secretKey'
			])
			->onlyMethods(['RevokeRefreshToken'])
			->getMock();

		// Dynamically compute the expected tokens
		$expectedTokens = array_map(function ($refreshToken) {
			return base64_encode(implode('|', [
				$refreshToken->UserId,
				$refreshToken->SeriesId,
				$refreshToken->Token
			]));
		}, $refreshTokens);
		$index          = 0;

		$tokenManagerMock
			->expects($this->exactly(2))
			->method('RevokeRefreshToken')
			->willReturnCallback(function ($refreshToken) use ($expectedTokens, &$index) {
				$this->assertSame($expectedTokens[$index], $refreshToken, 'Token mismatch at index ' . $index);
				$index++;
			});

		$tokenManagerMock->RevokeRefreshTokensByUserId('123');
	}

	public function testExtendRefreshTokenExpiration() {
		$refreshToken = new RefreshToken([
			'UserId'    => '123',
			'SeriesId'  => 1,
			'Token'     => 'token1',
			'ExpiresAt' => (new \DateTimeImmutable('-1 day'))->format('Y-m-d H:i:s') // Expired
		]);

		$this->refreshTokenMapperMock
			->method('GetRefreshToken')
			->with('123', 1, 'token1', false)
			->willReturn($refreshToken);

		$this->refreshTokenMapperMock
			->expects($this->once())
			->method('SaveRefreshToken')
			->with($this->callback(function ($updatedToken) {
				$newExpiration = (new \DateTimeImmutable())->add(new \DateInterval('P30D'))->format('Y-m-d H:i:s');
				$this->assertEquals($newExpiration, $updatedToken->ExpiresAt, 'Expiration date was not updated correctly.');
				return true;
			}));

		$tokenManager = new TokenManager(
			$this->refreshTokenMapperMock,
			$this->claimsProviderMock,
			'secretKey',
			'HS256',
			new \DateInterval('PT15M'), // Access token expiration
			new \DateInterval('P30D')  // Refresh token expiration
		);

		$tokenManager->ExtendRefreshTokenExpiration(base64_encode('123|1|token1'));
	}

	public function testDecodeAccessToken() {
		$payload     = [
			'userId' => 123,
			'iat' => time(),
			'exp' => time() + 3600
		];
		$accessToken = JWT::encode($payload, 'secretKey', 'HS256');

		$decoded = $this->tokenManager->DecodeAccessToken($accessToken);

		$this->assertEquals($payload, $decoded, 'Expected decoded token to match payload.');
	}

	public function testDecodeAccessTokenThrowsInvalidTokenException() {
		$this->expectException(InvalidTokenException::class);
		$this->expectExceptionMessage('Invalid or expired token');

		$this->tokenManager->DecodeAccessToken('invalid-token');
	}

	public function testDecodeRefreshTokenStringThrowsInvalidTokenException() {
		$this->expectException(InvalidTokenException::class);
		$this->expectExceptionMessage('Invalid refresh token format');

		// Invalid token string with only one part
		$this->invokePrivateMethod($this->tokenManager, 'decodeRefreshTokenString', ['invalid-base64']);
	}

	// Helper to invoke private/protected methods
	protected function invokePrivateMethod($object, string $methodName, array $parameters = []) {
		$reflection = new \ReflectionClass(get_class($object));
		$method     = $reflection->getMethod($methodName);
		$method->setAccessible(true);
		return $method->invokeArgs($object, $parameters);
	}
}
