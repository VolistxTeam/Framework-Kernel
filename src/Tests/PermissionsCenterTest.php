<?php

namespace Volistx\FrameworkKernel\Tests;

use Volistx\FrameworkKernel\Helpers\PermissionsCenter;

class PermissionsCenterTest extends TestCase
{
    public function testCheckWithMatchingPermission()
    {
        $key = (object) ['permissions' => ['subscriptions:view']];
        $module = 'subscriptions';
        $operation = 'view';

        $permissionsCenter = new PermissionsCenter();
        $result = $permissionsCenter->check($key, $module, $operation);

        $this->assertTrue($result);
    }

    public function testCheckWithWildcardPermission()
    {
        $key = (object) ['permissions' => ['*']];
        $module = 'plans';
        $operation = 'create';

        $permissionsCenter = new PermissionsCenter();
        $result = $permissionsCenter->check($key, $module, $operation);

        $this->assertTrue($result);
    }

    public function testCheckWithNoMatchingPermission()
    {
        $key = (object) ['permissions' => ['subscriptions:view']];
        $module = 'plans';
        $operation = 'create';

        $permissionsCenter = new PermissionsCenter();
        $result = $permissionsCenter->check($key, $module, $operation);

        $this->assertFalse($result);
    }

    public function testCheckWithEmptyPermissions()
    {
        $key = (object) ['permissions' => []];
        $module = 'subscriptions';
        $operation = 'view';

        $permissionsCenter = new PermissionsCenter();
        $result = $permissionsCenter->check($key, $module, $operation);

        $this->assertFalse($result);
    }

    public function testGetAdminPermissions()
    {
        $expectedResult = [
            '*', // Wildcard permission for all

            'user:*',
            'user:create',
            'user:update',
            'user:delete',
            'user:view',

            'subscriptions:*',
            'subscriptions:create',
            'subscriptions:mutate',
            'subscriptions:delete',
            'subscriptions:view',
            'subscriptions:view-all',
            'subscriptions:logs',
            'subscriptions:stats',
            'subscriptions:cancel',
            'subscriptions:uncancel',

            'personal-tokens:*',
            'personal-tokens:create',
            'personal-tokens:update',
            'personal-tokens:delete',
            'personal-tokens:reset',
            'personal-tokens:view',
            'personal-tokens:view-all',
            'personal-tokens:logs',

            'plans:*',
            'plans:create',
            'plans:update',
            'plans:delete',
            'plans:view',
            'plans:view-all',
            'plans:logs',

            'user-logs:*',
            'user-logs:view',
            'user-logs:view-all',

            'admin-logs:*',
            'admin-logs:view',
            'admin-logs:view-all',
        ];

        $permissionsCenter = new PermissionsCenter();
        $result = $permissionsCenter->getAdminPermissions();

        $this->assertEquals($expectedResult, $result);
    }
}
