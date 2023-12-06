<?php

namespace Volistx\FrameworkKernel\Tests;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Volistx\FrameworkKernel\AuthValidationRules\Users\IPValidationRule;
use Volistx\FrameworkKernel\Database\Factories\PersonalTokenFactory;
use Volistx\FrameworkKernel\Database\Factories\UserFactory;
use Volistx\FrameworkKernel\Enums\AccessRule;
use Volistx\FrameworkKernel\Facades\Messages;
use Volistx\FrameworkKernel\Facades\PersonalTokens;
use Wikimedia\IPSet;

class IPValidationRuleTest extends TestCase
{
    public function testAccessAllowedWhenIPRuleIsNone()
    {
        $user = $this->GenerateUser();
        $token = $this->generatePersonalToken($user->id, [
            'ip_rule' => AccessRule::NONE,
        ]);
        PersonalTokens::shouldReceive('getToken')->andReturn($token);

        $requestMock = $this->createMock(Request::class);
        $ipValidationRule = new IPValidationRule($requestMock);

        $result = $ipValidationRule->validate();

        $this->assertTrue($result);
    }

    public function testAccessDeniedWhenIPBlacklisted()
    {
        $user = $this->GenerateUser();
        $token = $this->generatePersonalToken($user->id, [
            'ip_rule' => AccessRule::BLACKLIST,
            'ip_range' => ["192.168.1.1", "192.168.2.1"]
            ]);
        PersonalTokens::shouldReceive('getToken')->andReturn($token);

        $ipSetMock = $this->createMock(IPSet::class);
        $ipSetMock->method('match')->willReturn(true);
        $this->mockIPSet($ipSetMock);

        $requestMock = $this->getRequestMock('192.168.1.1');
        $ipValidationRule = new IPValidationRule($requestMock);

        $result = $ipValidationRule->validate();

        $this->assertEquals(
            [
                'message' => Messages::E403(trans('volistx::service.not_allowed_to_access_from_your_ip')),
                'code' => 403,
            ],
            $result
        );
    }

    public function testAccessDeniedWhenIPWhitelisted()
    {
        $user = $this->GenerateUser();
        $token = $this->generatePersonalToken($user->id, [
            'ip_rule' => AccessRule::WHITELIST,
            'ip_range' => ["192.168.1.1", "192.168.2.1"]
        ]);
        PersonalTokens::shouldReceive('getToken')->andReturn($token);

        $ipSetMock = $this->createMock(IPSet::class);
        $ipSetMock->method('match')->willReturn(true);
        $this->mockIPSet($ipSetMock);

        $requestMock = $this->getRequestMock('111.111.1.1');
        $ipValidationRule = new IPValidationRule($requestMock);

        $result = $ipValidationRule->validate();

        $this->assertEquals(
            [
                'message' => Messages::E403(trans('volistx::service.not_allowed_to_access_from_your_ip')),
                'code' => 403,
            ],
            $result
        );
    }

    private function GenerateUser(): Collection|Model
    {
        return UserFactory::new()->create();
    }

    private function GeneratePersonalToken(string $user_id, array $inputs): Collection|Model
    {
        return PersonalTokenFactory::new()->create(array_merge(
                [
                    'user_id' => $user_id
                ],
                $inputs)
        );
    }

    private function mockIPSet($ipSetMock): void
    {
        // Mock the IPSet instance in the IPValidationRule class
        $this->app->instance(IPSet::class, $ipSetMock);
    }

    private function getRequestMock(string $clientIp): Request
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects($this->any())
            ->method('getClientIp')
            ->willReturn($clientIp);

        return $requestMock;
    }
}
