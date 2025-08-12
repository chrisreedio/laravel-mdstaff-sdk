<?php

namespace ChrisReedIO\MDStaff\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ChrisReedIO\MDStaff\MDStaff
 */
class MDStaff extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \ChrisReedIO\MDStaff\MDStaff::class;
    }
}
