<?php

declare(strict_types=1);

namespace ChrisReedIO\MDStaff\Support;

use InvalidArgumentException;

final class MDStaffConfig
{
    public static function accountCode(): string
    {
        return self::requiredString('account_code', 'MDStaff account code is not configured.');
    }

    public static function baseUrl(): string
    {
        return trim(self::requiredString('base_url', 'MDStaff base URL is not configured.'), '/');
    }

    public static function facilityId(): string
    {
        return self::requiredString('facility_id', 'MDStaff facility ID is not configured.');
    }

    public static function hasFacilityId(): bool
    {
        $facilityId = config('mdstaff-sdk.facility_id');

        return is_string($facilityId) && $facilityId !== '';
    }

    /**
     * @return array{client_id: string, client_secret: string}
     */
    public static function credentials(): array
    {
        $authType = config('mdstaff-sdk.auth.default', 'basic');
        $credentials = $authType === 'oauth'
            ? config('mdstaff-sdk.auth.oauth')
            : config('mdstaff-sdk.auth.basic');

        $clientId = is_array($credentials) ? $credentials['client_id'] ?? $credentials['username'] ?? null : null;
        $clientSecret = is_array($credentials) ? $credentials['client_secret'] ?? $credentials['password'] ?? null : null;

        if (! is_string($clientId) || $clientId === '' || ! is_string($clientSecret) || $clientSecret === '') {
            throw new InvalidArgumentException('MDStaff credentials are not configured.');
        }

        return ['client_id' => $clientId, 'client_secret' => $clientSecret];
    }

    public static function queryCacheTtl(): int
    {
        return max(1, (int) config('mdstaff-sdk.query_cache_ttl', 300));
    }

    public static function requestsPerMinute(): int
    {
        return max(1, (int) config('mdstaff-sdk.rate_limits.requests_per_minute', 10));
    }

    public static function budgetPerMinute(): int
    {
        return max(1, (int) config('mdstaff-sdk.rate_limits.budget_per_minute', 8));
    }

    private static function requiredString(string $key, string $message): string
    {
        $value = config("mdstaff-sdk.{$key}");

        if (! is_string($value) || $value === '') {
            throw new InvalidArgumentException($message);
        }

        return $value;
    }
}
