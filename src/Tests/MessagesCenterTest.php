<?php

use PHPUnit\Framework\TestCase;
use Volistx\FrameworkKernel\Helpers\MessagesCenter;

class MessagesCenterTest extends TestCase
{
    public function testError()
    {
        $type = 'InvalidParameter';
        $info = 'Some information about the error';
        $expectedResult = [
            'error' => [
                'type' => $type,
                'info' => $info,
            ],
        ];

        $messagesCenter = new MessagesCenter();
        $result = $messagesCenter->Error($type, $info);

        $this->assertEquals($expectedResult, $result);
    }

    public function testE400()
    {
        $error = 'Some specific error message';
        $expectedResult = [
            'error' => [
                'type' => 'InvalidParameter',
                'info' => $error,
            ],
        ];

        $messagesCenter = new MessagesCenter();
        $result = $messagesCenter->E400($error);

        $this->assertEquals($expectedResult, $result);
    }

    public function testE401()
    {
        $error = 'Some specific error message';
        $expectedResult = [
            'error' => [
                'type' => 'Unauthorized',
                'info' => $error,
            ],
        ];

        $messagesCenter = new MessagesCenter();
        $result = $messagesCenter->E401($error);

        $this->assertEquals($expectedResult, $result);
    }

    public function testE403()
    {
        $error = 'Some specific error message';
        $expectedResult = [
            'error' => [
                'type' => 'Forbidden',
                'info' => $error,
            ],
        ];

        $messagesCenter = new MessagesCenter();
        $result = $messagesCenter->E403($error);

        $this->assertEquals($expectedResult, $result);
    }

    public function testE404()
    {
        $error = 'Some specific error message';
        $expectedResult = [
            'error' => [
                'type' => 'NotFound',
                'info' => $error,
            ],
        ];

        $messagesCenter = new MessagesCenter();
        $result = $messagesCenter->E404($error);

        $this->assertEquals($expectedResult, $result);
    }

    public function testE409()
    {
        $error = 'Some specific error message';
        $expectedResult = [
            'error' => [
                'type' => 'Conflict',
                'info' => $error,
            ],
        ];

        $messagesCenter = new MessagesCenter();
        $result = $messagesCenter->E409($error);

        $this->assertEquals($expectedResult, $result);
    }

    public function testE429()
    {
        $error = 'Some specific error message';
        $expectedResult = [
            'error' => [
                'type' => 'RateLimitReached',
                'info' => $error,
            ],
        ];

        $messagesCenter = new MessagesCenter();
        $result = $messagesCenter->E429($error);

        $this->assertEquals($expectedResult, $result);
    }

    public function testE500()
    {
        $error = 'Some specific error message';
        $expectedResult = [
            'error' => [
                'type' => 'Unknown',
                'info' => $error,
            ],
        ];

        $messagesCenter = new MessagesCenter();
        $result = $messagesCenter->E500($error);

        $this->assertEquals($expectedResult, $result);
    }
}