<?php

namespace ChrisReedIO\MDStaff;

use ChrisReedIO\MDStaff\Commands\MDStaffCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MDStaffServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-mdstaff-sdk')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_mdstaff_sdk_table')
            ->hasCommand(MDStaffCommand::class);
    }
}
