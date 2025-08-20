<?php

namespace ChrisReedIO\MDStaff\Resources;

use ChrisReedIO\MDStaff\Requests\GetFacilities;
use Saloon\Http\BaseResource;
use Saloon\Http\Response;

class Facilities extends BaseResource
{
    public function list(): Response
    {
        return $this->connector->send(new GetFacilities);
    }
}
