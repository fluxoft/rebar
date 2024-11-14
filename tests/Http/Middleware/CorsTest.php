<?php

use Fluxoft\Rebar\Http\Request;
use Fluxoft\Rebar\Http\Response;
use Fluxoft\Rebar\Http\Middleware\Cors;
use Fluxoft\Rebar\Exceptions\CrossOriginException;
use Fluxoft\Rebar\Exceptions\MethodNotAllowedException;

class CorsTest extends \PHPUnit\Framework\TestCase {
    protected $request;
    protected $response;
    protected $cors;

    protected function setUp(): void {
        $this->request = new Request();
        $this->response = new Response();
        $this->cors = new Cors(['http://allowed-origin.com'], true);
    }

    public function testOptionsRequestReturns200() {
        $this->request->Method = 'OPTIONS';
        $response = $this->cors->Process($this->request, $this->response, function($req, $res) {
            return $res;
        });

        $this->assertEquals(200, $response->StatusCode);
        $this->assertEquals('OK', $response->Body);
    }

    public function testAllowedOriginSetsHeaders() {
        $this->request->Headers = ['Origin' => 'http://allowed-origin.com'];
        $this->request->Method = 'GET';

        $response = $this->cors->Process($this->request, $this->response, function($req, $res) {
            return $res;
        });

        $this->assertEquals('http://allowed-origin.com', $response->Headers['Access-Control-Allow-Origin']);
        $this->assertEquals('true', $response->Headers['Access-Control-Allow-Credentials']);
    }

    public function testDisallowedOriginThrowsException() {
        $this->request->Headers = ['Origin' => 'http://disallowed-origin.com'];
        $this->request->Method = 'GET';

        $this->expectException(CrossOriginException::class);
        $this->cors->Process($this->request, $this->response, function($req, $res) {
            return $res;
        });
    }

    public function testDisallowedMethodThrowsException() {
        $this->request->Headers = ['Origin' => 'http://allowed-origin.com'];
        $this->request->Method = 'PATCH'; // Assuming PATCH is not allowed

        $this->expectException(MethodNotAllowedException::class);
        $this->cors->Process($this->request, $this->response, function($req, $res) {
            return $res;
        });
    }
}
