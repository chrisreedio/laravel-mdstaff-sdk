<?php

declare(strict_types=1);

namespace ChrisReedIO\MDStaff\Facades;

use ChrisReedIO\MDStaff\MDStaffConnector;
use ChrisReedIO\MDStaff\Resources\Facilities;
use ChrisReedIO\MDStaff\Resources\LookUps;
use ChrisReedIO\MDStaff\Resources\Providers;
use Illuminate\Support\Facades\Facade;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Paginator;

/**
 * @method static Providers providers()
 * @method static Facilities facilities()
 * @method static LookUps lookUps()
 * @method static Response send(Request $request)
 * @method static Paginator paginate(Request $request)
 *
 * @see MDStaffConnector
 */
class MDStaff extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MDStaffConnector::class;
    }
}
