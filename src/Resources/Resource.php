<?php

declare(strict_types=1);

namespace ChrisReedIO\MDStaff\Resources;

use ChrisReedIO\MDStaff\MDStaffConnector;

abstract class Resource
{
    public function __construct(
        protected MDStaffConnector $connector,
    ) {}
}
