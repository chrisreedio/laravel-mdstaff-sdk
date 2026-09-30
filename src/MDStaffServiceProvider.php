<?php

declare(strict_types=1);

namespace ChrisReedIO\MDStaff;

use ChrisReedIO\MDStaff\Commands\TestConnectionCommand;
use ChrisReedIO\MDStaff\Support\MDStaffConfig;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MDStaffServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-mdstaff-sdk')
            ->hasConfigFile('mdstaff-sdk')
            ->hasCommand(TestConnectionCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->bind(MDStaffConnector::class, static fn (): MDStaffConnector => MDStaffConfig::hasFacilityId()
            ? MDStaffConnector::forConfiguredFacility()
            : new MDStaffConnector);
    }
}
