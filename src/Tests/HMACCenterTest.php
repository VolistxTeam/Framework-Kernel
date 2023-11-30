<?php

namespace Volistx\FrameworkKernel\Tests;

use Carbon\Carbon;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;
use stdClass;
use Symfony\Component\Uid\Ulid;
use Volistx\FrameworkKernel\Facades\PersonalTokens;
use Volistx\FrameworkKernel\Helpers\HMACCenter;

class HMACCenterTest extends TestCase
{
    public function testSign()
    {
        // Arrange
        app()->bind(
            \Illuminate\Http\Request::class,
            function () {
                $mock = Mockery::mock(\Illuminate\Http\Request::class)->makePartial();
                $mock->shouldReceive('method')->andReturn('GET');
                return $mock;
            }
        );
        $token = new stdClass();
        $token->hmac_token = 'test_token';
        PersonalTokens::shouldReceive('getToken')->andReturn($token);
        URL::shouldReceive('full')->andReturn('http://example.com');
        Carbon::setTestNow(Carbon::create(2022, 1, 1, 0, 0, 0));
        $stringUuid = '018c1f6a-332f-9145-6c47-f82692bc0a94';
        $uuid = Uuid::fromString($stringUuid);
        $factoryMock = Mockery::mock(UuidFactory::class . '[uuid4]', [
            'uuid4' => $uuid,
        ]);
        Uuid::setFactory($factoryMock);
        $content = ['key' => 'value'];
        $method = Request::method();
        $url = urlencode(URL::full());
        $nonce = $stringUuid;
        $timestamp = Carbon::now()->timestamp;
        $valueToSign = $method . $url . $nonce . $timestamp . json_encode($content);
        $expectedSignature = [
            'X-HMAC-Timestamp' => $timestamp,
            'X-HMAC-Content-SHA256' => base64_encode(hash_hmac('sha256', $valueToSign, 'test_token', true)),
            'X-HMAC-Nonce' => $nonce,
        ];

        // Act
        $signature = HMACCenter::sign($content);

        // Assert
        $this->assertEquals($expectedSignature, $signature);
    }

    public function testVerify()
    {
        $hmacToken = 'test_token';
        $method = 'GET';
        $url = 'http://example.com';
        $expectedResult = true;
        $nonce = '018c1f6a-332f-9145-6c47-f82692bc0a94';
        $timestamp = Carbon::now()->timestamp;
        $contentString = json_encode(['key' => 'value']);
        $valueToSign = $method . $url . $nonce . $timestamp . $contentString;

        $sha256 = base64_encode(hash_hmac('sha256', $valueToSign, $hmacToken, true));

        // Mock dependencies
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getHeader')
            ->with('X-Hmac-Timestamp')
            ->andReturn([$timestamp])
            ->shouldReceive('getHeader')
            ->with('X-Hmac-Nonce')
            ->andReturn([$nonce])
            ->shouldReceive('getHeader')
            ->with('X-Hmac-Content-Sha256')
            ->andReturn([$sha256])
            ->shouldReceive('getBody')
            ->andReturn(Mockery::mock(StreamInterface::class, ['getContents' => $contentString]))
            ->getMock();

        // Test case


        $result = HMACCenter::verify($hmacToken, $method, $url, $response);

        $this->assertEquals($expectedResult, $result);
    }
}