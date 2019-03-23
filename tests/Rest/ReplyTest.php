<?php

namespace Fluxoft\Rebar\Rest;

use PHPUnit\Framework\TestCase;

class ReplyTest extends TestCase {
	public function testReply() {
		$error = new Error('blah');
		$reply = new Reply(
			200,
			['data' => 'foo'],
			['meta' => 'bar'],
			new Error('blah')
		);
		$this->assertEquals(200, $reply->Status);
		$this->assertEquals(['data' => 'foo'], $reply->Data);
		$this->assertEquals(['meta' => 'bar'], $reply->Meta);
		$this->assertEquals($error, $reply->Error);
	}
}
