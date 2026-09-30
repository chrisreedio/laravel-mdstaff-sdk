<?php

declare(strict_types=1);

namespace ChrisReedIO\MDStaff\Requests;

use ChrisReedIO\MDStaff\Support\MDStaffConfig;
use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetFacilitiesRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/api/'.MDStaffConfig::accountCode().'/facilities';
    }
}
