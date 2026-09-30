<?php

declare(strict_types=1);

namespace ChrisReedIO\MDStaff\Exceptions;

use RuntimeException;

final class RequestBudgetExceededException extends RuntimeException
{
    public function __construct(public readonly int $releaseInSeconds)
    {
        parent::__construct('The MDStaff request budget has been reached.');
    }
}
