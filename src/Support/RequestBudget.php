<?php

declare(strict_types=1);

namespace ChrisReedIO\MDStaff\Support;

use ChrisReedIO\MDStaff\Exceptions\RequestBudgetExceededException;
use Illuminate\Support\Facades\RateLimiter;

/**
 * A per-minute budget for background MDStaff work that sits below the
 * connector's hard rate limit, so interactive requests keep some headroom.
 */
final class RequestBudget
{
    public function __construct(private readonly string $key = 'mdstaff:ingestion') {}

    public function reserve(): void
    {
        if (! RateLimiter::attempt($this->key, MDStaffConfig::budgetPerMinute(), static fn (): bool => true, 60)) {
            throw new RequestBudgetExceededException(max(1, RateLimiter::availableIn($this->key)));
        }
    }
}
