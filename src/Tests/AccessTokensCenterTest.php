<?php

namespace Volistx\FrameworkKernel\Tests;

use PHPUnit\Framework\TestCase;
use Volistx\FrameworkKernel\Helpers\AccessTokensCenter;

class AccessTokensCenterTest extends TestCase
{
    public function testSetToken()
    {
        $accessTokenCenter = new AccessTokensCenter();
        $token = 'test_token';

        $accessTokenCenter->setToken($token);

        $this->assertEquals($token, $accessTokenCenter->getToken());
    }

    public function testGetToken()
    {
        $accessTokenCenter = new AccessTokensCenter();
        $token = 'test_token';

        $accessTokenCenter->setToken($token);

        $this->assertEquals($token, $accessTokenCenter->getToken());
    }
}