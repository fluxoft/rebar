<?php

namespace Fluxoft\Rebar\Auth\Db;

use Fluxoft\Rebar\Auth\Exceptions\RefreshTokenNotFoundException;
use Fluxoft\Rebar\Auth\RefreshToken;
use Fluxoft\Rebar\Auth\RefreshTokenMapperInterface;
use Fluxoft\Rebar\Data\Db\Filter;
use Fluxoft\Rebar\Data\Db\Mappers\MapperInterface;

/**
 * Trait RefreshTokenMapperTrait
 * Provides default implementations for the RefreshTokenMapperInterface.
 *
 * This trait assumes that the implementing class:
 * - Implements the MapperInterface
 * - Implements the RefreshTokenMapperInterface
 * - Defines the $model property as an instance of RefreshToken
 *
 * @mixin \Fluxoft\Rebar\Data\Db\Mappers\MapperInterface
 * @mixin \Fluxoft\Rebar\Auth\Db\RefreshTokenMapperInterface
 *
 * @property-read RefreshToken $model
 */
trait RefreshTokenMapperTrait {
	/**
	 * @inheritDoc
	 */
	public function GetRefreshToken(
		mixed $userId,
		?int $seriesId = null,
		?string $token = null,
		bool $validOnly = false
	): ?RefreshToken {
		/** @var MapperInterface|RefreshTokenMapperTrait $this */
		$this->enforceMapperRequirements();

		$filters = [new Filter('UserId', '=', $userId)];
		if (isset($seriesId)) {
			$filters[] = new Filter('SeriesId', '=', $seriesId);
		}
		if (isset($token)) {
			$filters[] = new Filter('Token', '=', $token);
		}
		if ($validOnly) {
			$filters[] = new Filter('ExpiresAt', '>', date('Y-m-d H:i:s'));
			$filters[] = new Filter('RevokedAt', 'IS', null);
		}

		return $this->GetOne($filters);
	}

	/**
	 * @inheritDoc
	 */
	public function GetRefreshTokensByUserId(mixed $userId): array {
		/** @var MapperInterface|RefreshTokenMapperTrait $this */
		$this->enforceMapperRequirements();

		return $this->GetSet([
			new Filter('UserId', '=', $userId)
		]);
	}

	/**
	 * @inheritDoc
	 */
	public function SaveRefreshToken(RefreshToken $refreshToken): void {
		/** @var MapperInterface|RefreshTokenMapperTrait $this */
		$this->enforceMapperRequirements();

		$this->Save($refreshToken);
	}

	/**
	 * @inheritDoc
	 */
	public function RevokeRefreshToken(RefreshToken $refreshToken): void {
		/** @var MapperInterface|RefreshTokenMapperTrait $this */
		$this->enforceMapperRequirements();

		$refreshToken->RevokedAt = date('Y-m-d H:i:s');
		$refreshToken->ExpiresAt = $refreshToken->RevokedAt;

		$this->Save($refreshToken);
	}

	/**
	 * Ensure the class using this trait implements the required interface.
	 *
	 * @return void
	 * @throws \LogicException
	 */
	protected function enforceMapperRequirements(): void {
		// Ensure the class using this trait implements the RefreshTokenMapperInterface
		if (!($this instanceof RefreshTokenMapperInterface)) {
			throw new \LogicException(sprintf(
				'The class %s must implement %s to use %s.',
				static::class,
				RefreshTokenMapperInterface::class,
				__TRAIT__
			));
		}
		// Ensure the class using this trait implements the MapperInterface
		if (!($this instanceof MapperInterface)) {
			throw new \LogicException(sprintf(
				'The class %s must implement %s to use %s.',
				static::class,
				MapperInterface::class,
				__TRAIT__
			));
		}
		// Ensure the class's model is a RefreshToken
		if (!($this->model instanceof RefreshToken)) {
			throw new \LogicException(sprintf(
				'The model for the class %s must be an instance of %s to use %s.',
				static::class,
				RefreshToken::class,
				__TRAIT__
			));
		}
	}
}
