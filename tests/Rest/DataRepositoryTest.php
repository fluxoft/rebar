<?php

namespace Fluxoft\Rebar\Rest;

use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Doctrine\DBAL\Exception\DriverException;
use Fluxoft\Rebar\Db\Exceptions\InvalidModelException;
use Fluxoft\Rebar\Db\Mapper;
use Fluxoft\Rebar\Http\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataRepositoryTest extends TestCase {
	/** @var Mapper|MockObject */
	private $mapperObserver;
	/** @var MockObject|\Psr\Log\LoggerInterface */
	private $loggerObserver;
	/** @var \Fluxoft\Rebar\Auth\Db\User|MockObject */
	private $authUserObserver;
	/** @var Request|MockObject */
	private $requestObserver;
	/** @var \Fluxoft\Rebar\Db\Model|MockObject */
	private $dataModelObserver;
	protected function setup():void {
		$this->mapperObserver    = $this->getMockBuilder(
			'\Fluxoft\Rebar\Db\Mapper'
		)
			->disableOriginalConstructor()
			->getMock();
		$this->loggerObserver    = $this->getMockBuilder(
			'\Psr\Log\LoggerInterface'
		)
			->disableOriginalConstructor()
			->getMock();
		$this->authUserObserver  = $this->getMockBuilder(
			'\Fluxoft\Rebar\Auth\Db\User'
		)
			->disableOriginalConstructor()
			->getMock();
		$this->requestObserver   = $this->getMockBuilder(
			'\Fluxoft\Rebar\Http\Request'
		)
			->disableOriginalConstructor()
			->getMock();
		$this->dataModelObserver = $this->getMockBuilder(
			'\Fluxoft\Rebar\Db\Model'
		)
			->disableOriginalConstructor()
			->getMock();
	}

	protected function teardown():void {
		unset($this->dataModelObserver);
		unset($this->requestObserver);
		unset($this->authUserObserver);
		unset($this->loggerObserver);
		unset($this->mapperObserver);
	}

	public function testGetNullAuthUser() {
		$dataRepository = new DataRepository(
			$this->mapperObserver
		);
		$dataRepository->SetAuthUserFilter(true);
		$dataRepository->SetAuthUserIdProperty('UserId');

		$expectedReply = new Reply(403, [], [], new Error(403, 'Must be logged in to access this resource.'));

		$this->assertEquals($expectedReply, $dataRepository->Get($this->requestObserver, []));
	}

	/**
	 * @param $defaultPageSize
	 * @param bool $authUserFilter
	 * @param $authUserIdProperty
	 * @param array $getParams
	 * @param $returnSet
	 * @param $returnCount
	 * @dataProvider getSetProvider
	 */
	public function testGetSet(
		$defaultPageSize,
		bool $authUserFilter,
		$authUserIdProperty,
		array $getParams,
		$returnSet,
		$returnCount
	) {
		$dataRepository = new DataRepository(
			$this->mapperObserver,
			$this->loggerObserver,
			$this->authUserObserver
		);
		if (isset($defaultPageSize)) {
			$dataRepository->SetDefaultPageSize($defaultPageSize);
		}
		$dataRepository->SetAuthUserFilter($authUserFilter);
		$dataRepository->SetAuthUserIdProperty($authUserIdProperty);

		$this->requestObserver
			->expects($this->once())
			->method('Get')
			->will($this->returnValue($getParams));

		$get = $getParams;

		$page     = 1;
		$pageSize = $defaultPageSize;
		if (isset($get['page'])) {
			if (is_array($get['page'])) {
				if (isset($get['page']['number']) && is_numeric($get['page']['number'])) {
					$page = (int) $get['page']['number'];
				}
				if (isset($get['page']['size']) && is_numeric($get['page']['size'])) {
					$pageSize = (int) $get['page']['size'];
				}
			} elseif (is_numeric($get['page'])) {
				$page = (int) $get['page'];
			}
			if (isset($get['pageSize']) && is_numeric($get['pageSize'])) {
				$pageSize = (int) $get['pageSize'];
			}
		}

		unset($get['page']);
		unset($get['callback']);

		if ($authUserFilter) {
			$this->authUserObserver
				->expects($this->once())
				->method('GetID')
				->will($this->returnValue(2));
			$get[$authUserIdProperty] = 2;
		}

		$order = [];
		if (isset($get['order'])) {
			if (is_array($get['order'])) {
				$order = $get['order'];
			} else {
				$order = [$get['order']];
			}
			unset($get['order']);
		}

		$this->mapperObserver
			->expects($this->once())
			->method('GetSetWhere')
			->with($get, $order, $page, $pageSize)
			->will($this->returnValue($returnSet));
		$this->mapperObserver
			->expects($this->once())
			->method('CountWhere')
			->with($get)
			->will($this->returnValue($returnCount));

		$pages = (isset($pageSize) && $pageSize > 0) ? ceil($returnCount/$pageSize) : 1;

		$expectedReply       = new Reply();
		$expectedReply->Data = [];
		$expectedReply->Meta = [
			'page' => $page,
			'pages' => $pages,
			'count' => $returnCount
		];

		$this->assertEquals(
			$expectedReply,
			$dataRepository->Get($this->requestObserver, [])
		);
	}
	public function getSetProvider() {
		return [
			[
				'defaultPageSize' => null,
				'authUserFilter' => false,
				'authUserIdProperty' => 'UserId',
				'getParams' => [],
				'returnSet' => [],
				'returnCount' => 33
			],
			[
				'defaultPageSize' => 10,
				'authUserFilter' => false,
				'authUserIdProperty' => 'UserId',
				'getParams' => [
					'page' => 2
				],
				'returnSet' => [],
				'returnCount' => 33
			],
			[
				'defaultPageSize' => 10,
				'authUserFilter' => false,
				'authUserIdProperty' => 'UserId',
				'getParams' => [
					'page' => 2,
					'pageSize' => 12,
					'order' => 'First'
				],
				'returnSet' => [],
				'returnCount' => 33
			],
			[
				'defaultPageSize' => 10,
				'authUserFilter' => false,
				'authUserIdProperty' => 'UserId',
				'getParams' => [
					'page' => [
						'number' => 2,
						'size' => 15
					],
					'order' => ['First', 'Second']
				],
				'returnSet' => [],
				'returnCount' => 33
			],
			[
				'defaultPageSize' => 10,
				'authUserFilter' => true,
				'authUserIdProperty' => 'UserId',
				'getParams' => [],
				'returnSet' => [],
				'returnCount' => 33
			],
			[
				'defaultPageSize' => 10,
				'authUserFilter' => true,
				'authUserIdProperty' => 'UserId',
				'getParams' => [
					'UserId' => 1
				],
				'returnSet' => [],
				'returnCount' => 33
			]
		];
	}

	/**
	 * @param array $params
	 * @param $returnItem
	 * @param $authUserFilter
	 * @param $authUserIdProperty
	 * @param $userId
	 * @dataProvider GetOneProvider
	 */
	public function testGetOne(
		array $params,
		$returnItem,
		$authUserFilter,
		$authUserIdProperty,
		$userId
	) {
		$dataRepository = new DataRepository(
			$this->mapperObserver,
			$this->loggerObserver,
			$this->authUserObserver
		);
		$dataRepository->SetAuthUserFilter($authUserFilter);
		$dataRepository->SetAuthUserIdProperty($authUserIdProperty);

		$this->mapperObserver
			->expects($this->once())
			->method('GetOneById')
			->with($params[0])
			->will($this->returnValue($returnItem));

		$expectedReply = new Reply();

		if (!isset($returnItem)) {
			$expectedReply->Status = 404;
			$expectedReply->Error  = new Error(404, 'The requested item could not be found.');
		} else {
			if ($authUserFilter && $returnItem->$authUserIdProperty !== $userId) {
				$this->authUserObserver
					->expects($this->once())
					->method('GetID')
					->will($this->returnValue($userId));
				$expectedReply->Status = 404;
				$expectedReply->Error  = new Error(404, 'The requested item could not be found.');
			} else {
				$expectedReply->Data = $returnItem;
			}
		}

		$this->assertEquals(
			$expectedReply,
			$dataRepository->Get($this->requestObserver, $params)
		);
	}
	public function GetOneProvider() {
		$returnItem         = new \stdClass();
		$returnItem->UserId = 2;
		return [
			[
				'params' => [1],
				'returnItem' => [],
				'authUserFilter' => false,
				'authUserIdProperty' => 'UserId',
				'userId' => 1
			],
			[
				'params' => [1],
				'returnItem' => null,
				'authUserFilter' => false,
				'authUserIdProperty' => 'UserId',
				'userId' => 1
			],
			[
				'params' => [1],
				'returnItem' => $returnItem,
				'authUserFilter' => true,
				'authUserIdProperty' => 'UserId',
				'userId' => 1
			]
		];
	}

	public function testGetSubsetNoGetter() {
		$dataRepository = new DataRepository(
			$this->mapperObserver,
			$this->loggerObserver,
			$this->authUserObserver
		);

		$id         = 1;
		$subsetName = 'subset';

		$expectedReply         = new Reply();
		$expectedReply->Status = 404;
		$expectedReply->Error  = new Error(404, sprintf('"%s" not found.', $subsetName));

		$this->assertEquals(
			$expectedReply,
			$dataRepository->Get($this->requestObserver, [$id, $subsetName])
		);
	}

	public function testGetSubsetNoCount() {
		$id         = 1;
		$subsetName = 'subset';
		$getter     = 'Get'.ucwords($subsetName);
		$counter    = 'Count'.ucwords($subsetName);

		$mapperObserver = $this->getMockBuilder('\Fluxoft\Rebar\Db\Mapper')
			->setMethods([$getter])
			->disableOriginalConstructor()
			->getMock();

		$dataRepository = new DataRepository(
			$mapperObserver,
			$this->loggerObserver,
			$this->authUserObserver
		);

		$expectedReply         = new Reply();
		$expectedReply->Status = 500;
		$expectedReply->Error  = new Error(500, sprintf(
			'Counter method "%s" not found in mapper "%s"',
			$counter,
			get_class($mapperObserver)
		));

		$this->assertEquals(
			$expectedReply,
			$dataRepository->Get($this->requestObserver, [$id, $subsetName])
		);
	}

	public function testGetSubsetNoParent() {
		$id         = 1;
		$subsetName = 'subset';
		$getter     = 'Get'.ucwords($subsetName);
		$counter    = 'Count'.ucwords($subsetName);

		$mapperObserver = $this->getMockBuilder('\Fluxoft\Rebar\Db\Mapper')
			->setMethods([
				$getter,
				$counter,
				'GetOneById'
			])
			->disableOriginalConstructor()
			->getMock();

		$dataRepository = new DataRepository(
			$mapperObserver,
			$this->loggerObserver,
			$this->authUserObserver
		);

		$mapperObserver
			->expects($this->once())
			->method('GetOneById')
			->with($id)
			->will($this->returnValue(null));

		$expectedReply = new Reply();

		$expectedReply->Status = 404;
		$expectedReply->Error  = new Error(
			404,
			'The requested item could not be found.',
			sprintf(
				'The parent with id "%s" was not found.',
				$id
			)
		);

		$this->assertEquals($expectedReply, $dataRepository->Get(
			$this->requestObserver,
			[$id, $subsetName]
		));
	}

	public function testGetSubsetNotAuthorized() {
		$id         = 1;
		$subsetName = 'subset';
		$getter     = 'Get'.ucwords($subsetName);
		$counter    = 'Count'.ucwords($subsetName);

		$mapperObserver = $this->getMockBuilder('\Fluxoft\Rebar\Db\Mapper')
			->setMethods([
				$getter,
				$counter,
				'GetOneById'
			])
			->disableOriginalConstructor()
			->getMock();

		$dataRepository = new DataRepository(
			$mapperObserver,
			$this->loggerObserver,
			$this->authUserObserver
		);

		$authUserIdProperty = 'UserId';
		$authUserId         = 1;
		$nonAuthUserId      = 2;

		$dataRepository->SetAuthUserFilter(true);
		$dataRepository->SetAuthUserIdProperty($authUserIdProperty);

		$parentModel = $this->getMockBuilder('\Fluxoft\Rebar\Db\Model')
			->disableOriginalConstructor()
			->getMock();

		$mapperObserver
			->expects($this->once())
			->method('GetOneById')
			->with($id)
			->will($this->returnValue($parentModel));

		$parentModel
			->expects($this->once())
			->method('__get')
			->with($authUserIdProperty)
			->will($this->returnValue($nonAuthUserId));

		$this->authUserObserver
			->expects($this->once())
			->method('GetID')
			->will($this->returnValue($authUserId));

		$expectedReply = new Reply();

		$expectedReply->Status = 404;
		$expectedReply->Error  = new Error(
			404,
			'The requested item could not be found.',
			sprintf(
				'The parent with id "%s" was not found.',
				$id
			)
		);

		$this->assertEquals($expectedReply, $dataRepository->Get(
			$this->requestObserver,
			[$id, $subsetName]
		));
	}

	/**
	 * @param array $params
	 * @param array $getParams
	 * @param int $defaultPageSize
	 * @param int $totalRecords
	 * @param array $returnSet
	 * @dataProvider GetSubsetProvider
	 */
	public function testGetSubset(
		array $params,
		array $getParams,
		int   $defaultPageSize,
		int   $totalRecords,
		array $returnSet = null
	) {
		$id         = $params[0];
		$subsetName = $params[1];
		$getter     = 'Get'.ucwords($subsetName);
		$counter    = 'Count'.ucwords($subsetName);

		$mapperObserver = $this->getMockBuilder('\Fluxoft\Rebar\Db\Mapper')
			->setMethods([
				$getter,
				$counter,
				'GetOneById'
			])
			->disableOriginalConstructor()
			->getMock();

		$dataRepository = new DataRepository(
			$mapperObserver,
			$this->loggerObserver,
			$this->authUserObserver
		);

		$dataRepository->SetAuthUserFilter(true);
		$dataRepository->SetAuthUserIdProperty('UserId');
		$dataRepository->SetDefaultPageSize($defaultPageSize);

		$parentModel = $this->getMockBuilder('\Fluxoft\Rebar\Db\Model')
			->disableOriginalConstructor()
			->getMock();

		// get parent model
		$mapperObserver
			->expects($this->once())
			->method('GetOneById')
			->with($id)
			->will($this->returnValue($parentModel));

		// authorize access
		$parentModel
			->expects($this->once())
			->method('__get')
			->with('UserId')
			->will($this->returnValue(1));
		$this->authUserObserver
			->expects($this->once())
			->method('GetID')
			->will($this->returnValue(1));

		// paging stuff
		$this->requestObserver
			->expects($this->once())
			->method('Get')
			->will($this->returnValue($getParams));

		$get      = $getParams;
		$page     = 1;
		$pageSize = $defaultPageSize;
		if (isset($get['page'])) {
			if (is_array($get['page'])) {
				if (isset($get['page']['number']) && is_numeric($get['page']['number'])) {
					$page = (int) $get['page']['number'];
				}
				if (isset($get['page']['size']) && is_numeric($get['page']['size'])) {
					$pageSize = (int) $get['page']['size'];
				}
			} elseif (is_numeric($get['page'])) {
				$page = (int) $get['page'];
			}
			if (isset($get['pageSize']) && is_numeric($get['pageSize'])) {
				$pageSize = (int) $get['pageSize'];
			}
		}

		// parent id
		$parentModel
			->expects($this->once())
			->method('GetID')
			->will($this->returnValue($id));

		$mapperObserver
			->expects($this->once())
			->method($getter)
			->with($id, $page, $pageSize)
			->will($this->returnValue($returnSet));

		$expectedReply = new Reply();

		if (!isset($returnSet)) {
			$expectedReply->Status = 404;
			$expectedReply->Error  = new Error(
				404,
				'The requested item could not be found.',
				sprintf(
					'The subset "%s" returned a null result.',
					$subsetName
				)
			);
		} else {
			$mapperObserver
				->expects($this->once())
				->method($counter)
				->with($id)
				->will($this->returnValue($totalRecords));
			$pages = (isset($pageSize) && $pageSize > 0) ? ceil($totalRecords/$pageSize) : 1;

			$expectedReply->Data = $returnSet;
			$expectedReply->Meta = [
				'page' => $page,
				'pages' => $pages,
				'count' => $totalRecords
			];
		}

		$this->assertEquals($expectedReply, $dataRepository->Get(
			$this->requestObserver,
			$params
		));
	}
	public function GetSubsetProvider() {
		return [
			[
				'params' => [1, 'subset'],
				'getParams' => [],
				'defaultPageSize' => 10,
				'totalRecords' => 25
			],
			[
				'params' => [1, 'subset'],
				'getParams' => [],
				'defaultPageSize' => 10,
				'totalRecords' => 20,
				'returnSet' => []
			],
			[
				'params' => [1, 'subset'],
				'getParams' => [
					'page' => 2
				],
				'defaultPageSize' => 10,
				'totalRecords' => 20,
				'returnSet' => []
			],
			[
				'params' => [1, 'subset'],
				'getParams' => [
					'page' => 2,
					'pageSize' => 12
				],
				'defaultPageSize' => 10,
				'totalRecords' => 20,
				'returnSet' => []
			],
			[
				'params' => [1, 'subset'],
				'getParams' => [
					'page' => [
						'number' => 2,
						'size' => 15
					]
				],
				'defaultPageSize' => 10,
				'totalRecords' => 20,
				'returnSet' => []
			]
		];
	}

	public function testPostUnauthorized() {
		$dataRepository = $this->getMockBuilder('\Fluxoft\Rebar\Rest\DataRepository')
			->setConstructorArgs([
				$this->mapperObserver
			])
			->setMethods(['log', 'getInputData'])
			->getMock();
		$dataRepository->SetAuthUserFilter(true);

		/*$dataRepository
			->expects($this->once())
			->method('getInputData')
			->with($this->requestObserver)
			->will($this->returnValue([]));*/

		$expectedReply = new Reply(403, [], [], new Error(403, 'Must be logged in to access this resource.'));

		$this->assertEquals(
			$expectedReply,
			$dataRepository->Post($this->requestObserver)
		);
	}

	/**
	 * @param array $model
	 * @param int $authUserId
	 * @param string|null $expectedExceptionClass
	 * @dataProvider PostProvider
	 */
	public function testPost(
		array  $model,
		int    $authUserId,
		string $expectedExceptionClass = null
	) {
		$dataRepository = $this->getMockBuilder('\Fluxoft\Rebar\Rest\DataRepository')
			->setConstructorArgs([
				$this->mapperObserver,
				$this->loggerObserver,
				$this->authUserObserver
			])
			->onlyMethods(['log', 'getInputData'])
			->getMock();
		$dataRepository->SetAuthUserFilter(true);
		$dataRepository->SetAuthUserIdProperty('UserId');

		$dataRepository
			->expects($this->once())
			->method('getInputData')
			->with($this->requestObserver)
			->will($this->returnValue($model));

		$this->authUserObserver
			->expects($this->once())
			->method('GetID')
			->will($this->returnValue($authUserId));

		$this->mapperObserver
			->expects($this->once())
			->method('GetNew')
			->will($this->returnValue($this->dataModelObserver));

		$this->dataModelObserver
			->expects($this->any())
			->method('__set');

		$expectedReply = new Reply();
		if (isset($expectedExceptionClass)) {
			$expectedException = new $expectedExceptionClass('exception');

			$this->mapperObserver
				->expects($this->once())
				->method('Save')
				->with($this->dataModelObserver)
				->willThrowException($expectedException);

			switch($expectedExceptionClass) {
				case '\Fluxoft\Rebar\Db\Exceptions\InvalidModelException':
					$this->dataModelObserver
						->expects($this->once())
						->method('GetValidationErrors')
						->will($this->returnValue([]));
					$expectedReply->Status = 422;
					$expectedReply->Error  = new Error(
						422,
						'Validation failed.',
						['invalidProperties' => []],
						$expectedException
					);
					break;
				case '\InvalidArgumentException':
					$expectedReply->Status = 422;
					$expectedReply->Error  = new Error(
						422,
						'Invalid argument',
						$expectedException->getMessage(),
						$expectedException
					);
					break;
				case '\Doctrine\DBAL\Exception\UniqueConstraintViolationException':
					$expectedReply->Status = 409;
					$expectedReply->Error  = new Error(409, 'Object already exists');
					break;
				case '\Doctrine\DBAL\Exception':
					$dataRepository
						->expects($this->once())
						->method('log')
						->with('error', $expectedException->getMessage());
					$expectedReply->Status = 500;
					$expectedReply->Error  = new Error(
						500,
						'Database error. Please try again later.',
						null,
						$expectedException
					);
					break;
				case '\Exception':
					$expectedReply->Status = 500;
					$expectedReply->Error  = new Error(
						500,
						'Unknown error occurred.',
						null,
						$expectedException
					);
					break;
			}
		} else {
			$this->mapperObserver
				->expects($this->once())
				->method('Save')
				->with($this->dataModelObserver);
			$this->mapperObserver
				->expects($this->once())
				->method('GetOneById')
				->will($this->returnValue($this->dataModelObserver));

			$expectedReply->Status = 201;
			$expectedReply->Data   = $this->dataModelObserver;
		}

		$this->assertEquals(
			$expectedReply,
			$dataRepository->Post($this->requestObserver)
		);
	}
	public function PostProvider() {
		return [
			[
				'model' => [
					'UserId' => 1,
					'Foo' => 'Bar'
				],
				'authUserId' => 1
			],
			[
				'model' => [
					'UserId' => 2,
					'Foo' => 'Bar'
				],
				'authUserId' => 1
			],
			[
				'model' => [
					'Foo' => 'Bar'
				],
				'authUserId' => 1
			],
			[
				'model' => [
					'UserId' => 1,
					'Foo' => 'Bar'
				],
				'authUserId' => 1,
				'exception' => '\InvalidArgumentException'
			],
			[
				'model' => [
					'UserId' => 1,
					'Foo' => 'Bar'
				],
				'authUserId' => 1,
				'exception' => '\Doctrine\DBAL\Exception'
			],
			[
				'model' => [
					'UserId' => 1,
					'Foo' => 'Bar'
				],
				'authUserId' => 1,
				'exception' => '\Exception'
			]
		];
	}

	public function testPutNoId() {
		$dataRepository = new DataRepository(
			$this->mapperObserver
		);

		$expectedReply = new Reply(422, [], [], new Error(422, 'You must specify an ID in order to update.'));

		$this->assertEquals(
			$expectedReply,
			$dataRepository->Put($this->requestObserver)
		);
	}

	public function testPutUnauthorized() {
		$dataRepository = new DataRepository(
			$this->mapperObserver
		);
		$dataRepository->SetAuthUserFilter(true);

		$expectedReply = new Reply(403, [], [], new Error(403, 'Must be logged in to access this resource.'));

		$this->assertEquals(
			$expectedReply,
			$dataRepository->Put($this->requestObserver, [1])
		);
	}

	public function testPutModelNotFound() {
		$id = 1;

		$dataRepository = new DataRepository(
			$this->mapperObserver
		);

		$this->mapperObserver
			->expects($this->once())
			->method('GetOneById')
			->with($id)
			->will($this->returnValue(null));

		$expectedReply = new Reply(404, [], [], new Error(404, 'The object to be updated was not found.'));

		$this->assertEquals(
			$expectedReply,
			$dataRepository->Put($this->requestObserver, [$id])
		);
	}

	public function testPutModelWrongUser() {
		$id                 = 1;
		$authUserIdProperty = 'UserId';

		$dataRepository = new DataRepository(
			$this->mapperObserver,
			$this->loggerObserver,
			$this->authUserObserver
		);
		$dataRepository->SetAuthUserFilter(true);
		$dataRepository->SetAuthUserIdProperty($authUserIdProperty);

		$this->mapperObserver
			->expects($this->once())
			->method('GetOneById')
			->with($id)
			->will($this->returnValue($this->dataModelObserver));
		$this->dataModelObserver
			->expects($this->once())
			->method('__get')
			->with($authUserIdProperty)
			->will($this->returnValue(2)); // not the authorized user
		$this->authUserObserver
			->expects($this->once())
			->method('GetID')
			->will($this->returnValue($id));

		$expectedReply = new Reply(404, [], [], new Error(404, 'The object to be updated was not found.'));

		$this->assertEquals(
			$expectedReply,
			$dataRepository->Put($this->requestObserver, [$id])
		);
	}

	public function testPutModelErrors() {
		$id = 1;

		$dataRepository = $this->getMockBuilder(
			'\Fluxoft\Rebar\Rest\DataRepository'
		)
			->setConstructorArgs([$this->mapperObserver])
			->setMethods(['getInputData'])
			->getMock();

		$this->mapperObserver
			->expects($this->once())
			->method('GetOneById')
			->with($id)
			->will($this->returnValue($this->dataModelObserver));
		$dataRepository
			->expects($this->once())
			->method('getInputData')
			->with($this->requestObserver)
			->will($this->returnValue([
				'foo' => 'bar'
			]));
		$exception = new \InvalidArgumentException('invalid argument');
		$this->dataModelObserver
			->expects($this->once())
			->method('__set')
			->with('foo', 'bar')
			->willThrowException($exception);
		$errors = ['invalid argument'];

		$expectedReply = new Reply(422, [], [], new Error(422, 'Errors saving properties', ['errors' => $errors]));

		$this->assertEquals(
			$expectedReply,
			$dataRepository->Put($this->requestObserver, [$id])
		);
	}

	public function testPutInvalidModel() {
		$id = 1;

		$dataRepository = $this->getMockBuilder(
			'\Fluxoft\Rebar\Rest\DataRepository'
		)
			->setConstructorArgs([$this->mapperObserver])
			->setMethods(['getInputData'])
			->getMock();

		$this->mapperObserver
			->expects($this->once())
			->method('GetOneById')
			->with($id)
			->will($this->returnValue($this->dataModelObserver));
		$dataRepository
			->expects($this->once())
			->method('getInputData')
			->with($this->requestObserver)
			->will($this->returnValue([]));
		$exception = new InvalidModelException('invalid model');
		$this->mapperObserver
			->expects($this->once())
			->method('Save')
			->with($this->dataModelObserver)
			->willThrowException($exception);
		$this->dataModelObserver
			->expects($this->once())
			->method('GetValidationErrors')
			->will($this->returnValue(['foo' => 'bar']));

		$expectedReply = new Reply(
			422,
			[],
			[],
			new Error(
				422,
				'Validation failed.',
				['invalidProperties' => ['foo' => 'bar']],
				$exception
			)
		);

		$this->assertEquals(
			$expectedReply,
			$dataRepository->Put($this->requestObserver, [$id])
		);
	}

	public function testPut() {
		$id = 1;

		$dataRepository = $this->getMockBuilder(
			'\Fluxoft\Rebar\Rest\DataRepository'
		)
			->setConstructorArgs([$this->mapperObserver])
			->setMethods(['getInputData'])
			->getMock();

		$this->mapperObserver
			->expects($this->once())
			->method('GetOneById')
			->with($id)
			->will($this->returnValue($this->dataModelObserver));
		$dataRepository
			->expects($this->once())
			->method('getInputData')
			->with($this->requestObserver)
			->will($this->returnValue([]));
		$this->mapperObserver
			->expects($this->once())
			->method('Save')
			->with($this->dataModelObserver);

		$expectedReply = new Reply(200, $this->dataModelObserver);

		$this->assertEquals(
			$expectedReply,
			$dataRepository->Put($this->requestObserver, [$id])
		);
	}

	public function testPatch() {
		$dataRepository = $this->getMockBuilder(
			'\Fluxoft\Rebar\Rest\DataRepository'
		)
			->disableOriginalConstructor()
			->setMethods(['Put'])
			->getMock();
		$dataRepository
			->expects($this->once())
			->method('Put')
			->with($this->requestObserver, []);
		$dataRepository->Patch($this->requestObserver, []);
	}

	public function testDeleteNoId() {
		$dataRepository = new DataRepository(
			$this->mapperObserver
		);

		$expectedReply = new Reply(422, [], [], new Error(422, 'ID is required for DELETE operation.'));

		$this->assertEquals(
			$expectedReply,
			$dataRepository->Delete($this->requestObserver, [])
		);
	}

	public function testDeleteUnauthorized() {
		$dataRepository = new DataRepository(
			$this->mapperObserver
		);
		$dataRepository->SetAuthUserFilter(true);

		$expectedReply = new Reply(403, [], [], new Error(403, 'Must be logged in to access this resource.'));

		$this->assertEquals(
			$expectedReply,
			$dataRepository->Delete($this->requestObserver, [1])
		);
	}

	public function testDeleteModelNotFound() {
		$id = 1;

		$dataRepository = new DataRepository(
			$this->mapperObserver
		);

		$this->mapperObserver
			->expects($this->once())
			->method('GetOneById')
			->with($id)
			->will($this->returnValue(null));

		$expectedReply = new Reply(404, [], [], new Error(404, 'The object to be deleted was not found.'));

		$this->assertEquals(
			$expectedReply,
			$dataRepository->Delete($this->requestObserver, [$id])
		);
	}

	public function testDeleteModelWrongUser() {
		$id                 = 1;
		$authUserIdProperty = 'UserId';

		$dataRepository = new DataRepository(
			$this->mapperObserver,
			$this->loggerObserver,
			$this->authUserObserver
		);
		$dataRepository->SetAuthUserFilter(true);
		$dataRepository->SetAuthUserIdProperty($authUserIdProperty);

		$this->mapperObserver
			->expects($this->once())
			->method('GetOneById')
			->with($id)
			->will($this->returnValue($this->dataModelObserver));
		$this->dataModelObserver
			->expects($this->once())
			->method('__get')
			->with($authUserIdProperty)
			->will($this->returnValue(2)); // not the authorized user
		$this->authUserObserver
			->expects($this->once())
			->method('GetID')
			->will($this->returnValue($id));

		$expectedReply = new Reply(404, [], [], new Error(404, 'The object to be deleted was not found.'));

		$this->assertEquals(
			$expectedReply,
			$dataRepository->Delete($this->requestObserver, [$id])
		);
	}

	public function testDelete() {
		$id = 1;

		$dataRepository = $this->getMockBuilder(
			'\Fluxoft\Rebar\Rest\DataRepository'
		)
			->setConstructorArgs([$this->mapperObserver])
			->setMethods(['getInputData'])
			->getMock();

		$this->mapperObserver
			->expects($this->once())
			->method('GetOneById')
			->with($id)
			->will($this->returnValue($this->dataModelObserver));
		$this->mapperObserver
			->expects($this->once())
			->method('Delete')
			->with($this->dataModelObserver);

		$expectedReply = new Reply(204, ['success' => 'The item was deleted.']);

		$this->assertEquals(
			$expectedReply,
			$dataRepository->Delete($this->requestObserver, [$id])
		);
	}

	public function testGetTooManyParams() {
		$dataRepository = new DataRepository(
			$this->mapperObserver
		);

		$expectedReply         = new Reply();
		$expectedReply->Status = 400;
		$expectedReply->Error  = new Error(400, 'Too many parameters in URL.');

		$this->assertEquals(
			$expectedReply,
			$dataRepository->Get(
				$this->requestObserver,
				[
					1,
					'subset',
					'something_else'
				]
			)
		);
	}

	/**
	 * @param $body
	 * @param $method
	 * @param $vars
	 * @param $expectedData
	 * @dataProvider getInputDataProvider
	 */
	public function test_getInputData(
		string $body,
		string $method,
		array  $vars,
		array  $expectedData
	) {
		$dataRepository = new MockDataRepository(
			$this->mapperObserver
		);

		$getMethodMap = [
			['Body', $body],
			['Method', $method]
		];
		$this->requestObserver
			->expects($this->any())
			->method('__get')
			->will($this->returnValueMap([
				['Body', $body],
				['Method', $method]
			]));

		$this->requestObserver
			->expects($this->once())
			->method($method)
			->will($this->returnValue($vars));

		$this->assertEquals(
			$expectedData,
			$dataRepository->PublicGetInputData($this->requestObserver)
		);
	}
	public function getInputDataProvider() {
		return [
			'noneFound' => [
				'body' => '',
				'method' => 'POST',
				'vars' => [],
				'expectedData' => []
			],
			'fromModelVars' => [
				'body' => '',
				'method' => 'POST',
				'vars' => [
					'model' => json_encode(
						['foo' => 'bar']
					)
				],
				'expectedData' => [
					'foo' => 'bar'
				]
			],
			'fromVars' => [
				'body' => '',
				'method' => 'POST',
				'vars' => [
					'foo' => 'bar'
				],
				'expectedData' => [
					'foo' => 'bar'
				]
			],
			'fromBody' => [
				'body' => json_encode(
					['foo' => 'bar']
				),
				'method' => 'POST',
				'vars' => [],
				'expectedData' => [
					'foo' => 'bar'
				]
			]
		];
	}

	/**
	 * @param string $type
	 * @param string $message
	 * @dataProvider logProvider
	 */
	public function test_log(
		string $type,
		string $message
	) {
		$dataRepository = new MockDataRepository(
			$this->mapperObserver,
			$this->loggerObserver
		);

		$this->loggerObserver
			->expects($this->once())
			->method($type)
			->with($message);

		$dataRepository->PublicLog($type, $message);
	}
	public function logProvider() {
		return [
			['emergency', 'Some emergency message.'],
			['alert', 'Some alert message.'],
			['critical', 'Some critical message.'],
			['error', 'Some error message.'],
			['warning', 'Some warning message.'],
			['notice', 'Some notice message.'],
			['info', 'Some info message.'],
			['debug', 'Some debug message.']
		];
	}
}

// @codingStandardsIgnoreStart
class MockDataRepository extends DataRepository {
	// @codingStandardsIgnoreEnd

	public function PublicGetInputData(Request $request) {
		return $this->getInputData($request);
	}

	public function PublicLog($type, $message) {
		$this->log($type, $message);
	}
}
