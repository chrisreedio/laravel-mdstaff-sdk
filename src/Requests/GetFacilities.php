<?php

namespace ChrisReedIO\MDStaff\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetFacilities extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        $accountCode = config('mdstaff-sdk.account_code');

        return "/api/{$accountCode}/facilities";
    }
}
