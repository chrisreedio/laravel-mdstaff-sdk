<?php

namespace ChrisReedIO\MDStaff\Tests;

use ChrisReedIO\MDStaff\MDStaffServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            MDStaffServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('cache.default', 'array');
        config()->set('mdstaff-sdk.base_url', 'api.asm-cloud.com');
        config()->set('mdstaff-sdk.account_code', 'example');
        config()->set('mdstaff-sdk.facility_id', 'facility-id');
        config()->set('mdstaff-sdk.auth.default', 'basic');
        config()->set('mdstaff-sdk.auth.basic.username', 'username');
        config()->set('mdstaff-sdk.auth.basic.password', 'password');
    }
}
