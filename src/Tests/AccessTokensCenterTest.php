<?php

namespace Volistx\FrameworkKernel\Tests;

use Volistx\FrameworkKernel\Helpers\AccessTokensCenter;

class AccessTokensCenterTest extends TestCase
{
    private ?AccessTokensCenter $accessTokenCenter;

    protected function setUp(): void
    {
        $this->accessTokenCenter = new AccessTokensCenter();
    }

    protected function tearDown(): void
    {
        $this->accessTokenCenter = null;
    }

    public function testSetToken()
    {
        $token = 'test_token';
        $this->accessTokenCenter->setToken($token);

        $this->assertEquals($token, $this->accessTokenCenter->getToken());
    }

    public function testGetToken()
    {
        $token = 'test_token';
        $this->accessTokenCenter->setToken($token);

        $this->assertEquals($token, $this->accessTokenCenter->getToken());
    }
}
