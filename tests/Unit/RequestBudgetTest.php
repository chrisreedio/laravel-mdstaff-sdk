<?php

use ChrisReedIO\MDStaff\Exceptions\RequestBudgetExceededException;
use ChrisReedIO\MDStaff\Support\RequestBudget;
use Illuminate\Support\Facades\RateLimiter;

it('reserves requests up to the configured budget', function () {
    config()->set('mdstaff-sdk.rate_limits.budget_per_minute', 2);
    $budget = app(RequestBudget::class);

    $budget->reserve();
    $budget->reserve();

    expect(fn () => $budget->reserve())->toThrow(RequestBudgetExceededException::class);
});

it('reports when the exhausted budget becomes available again', function () {
    RateLimiter::hit('mdstaff:ingestion', 60);
    config()->set('mdstaff-sdk.rate_limits.budget_per_minute', 1);

    try {
        app(RequestBudget::class)->reserve();
        $this->fail('The request budget should have been exhausted.');
    } catch (RequestBudgetExceededException $exception) {
        expect($exception->releaseInSeconds)->toBeGreaterThan(0)->toBeLessThanOrEqual(60);
    }
});

it('tracks separately keyed budgets independently', function () {
    config()->set('mdstaff-sdk.rate_limits.budget_per_minute', 1);

    (new RequestBudget('mdstaff:first'))->reserve();
    (new RequestBudget('mdstaff:second'))->reserve();

    expect(fn () => (new RequestBudget('mdstaff:first'))->reserve())
        ->toThrow(RequestBudgetExceededException::class);
});
