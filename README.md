# MDStaff SDK for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/chrisreedio/laravel-mdstaff-sdk.svg?style=flat-square)](https://packagist.org/packages/chrisreedio/laravel-mdstaff-sdk)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/chrisreedio/laravel-mdstaff-sdk/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/chrisreedio/laravel-mdstaff-sdk/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/chrisreedio/laravel-mdstaff-sdk/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/chrisreedio/laravel-mdstaff-sdk/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/chrisreedio/laravel-mdstaff-sdk.svg?style=flat-square)](https://packagist.org/packages/chrisreedio/laravel-mdstaff-sdk)

A read-only Laravel SDK for the MDStaff (ASM Cloud) Query API, built on [Saloon](https://docs.saloon.dev). It handles facility-scoped OAuth tokens, response caching, rate limiting, and pagination, and ships documented query presets for provider records.

Supports Laravel 11–13 and Saloon 3.10+ or 4.x. Saloon releases below 4.0 carry security advisories, so prefer Saloon 4.

## Installation

```bash
composer require chrisreedio/laravel-mdstaff-sdk
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag="mdstaff-sdk-config"
```

## Configuration

```env
MDSTAFF_BASE_URL=api.asm-cloud.com
MDSTAFF_ACCOUNT_CODE=your-account-code
MDSTAFF_FACILITY_ID=your-facility-uid

# "basic" sends the username/password pair as OAuth client credentials; "oauth" sends the client ID/secret pair.
MDSTAFF_AUTH_DEFAULT=basic
MDSTAFF_BASIC_USERNAME=
MDSTAFF_BASIC_PASSWORD=
MDSTAFF_OAUTH_CLIENT_ID=
MDSTAFF_OAUTH_CLIENT_SECRET=

# Optional
MDSTAFF_QUERY_CACHE_TTL=300
MDSTAFF_REQUESTS_PER_MINUTE=10
MDSTAFF_BUDGET_PER_MINUTE=8
```

Verify the connection with a read-only smoke test that requests one Demographic record and prints only its field names:

```bash
php artisan mdstaff:test
```

## Usage

The `MDStaff` facade resolves a connector scoped to `MDSTAFF_FACILITY_ID`. The facility access token is requested on the first send and cached until shortly before it expires.

### Providers

```php
use ChrisReedIO\MDStaff\Enums\QuerySource;
use ChrisReedIO\MDStaff\Facades\MDStaff;
use ChrisReedIO\MDStaff\Queries\ProviderQueries;

// Name search: matches "Jane Doe" and "Doe, Jane"
$matches = MDStaff::providers()->search('Jane Doe');

// A single Demographic record, using the preset Demographic fields
$provider = MDStaff::providers()->findByNpi('1234567890');
$provider = MDStaff::providers()->find($providerId);

// Every record of one source, paged lazily at 2,000 records per request
MDStaff::providers()->records(QuerySource::Address)->each(function (array $address) {
    // ...
});

// One provider's records, or only records changed within a date range
MDStaff::providers()->records(QuerySource::BoardCertification, providerId: $providerId);
MDStaff::providers()->records(
    QuerySource::Demographic,
    filter: ProviderQueries::lastUpdatedBetween(now()->subDay(), now()),
);

// The latest in-use JPEG/PNG headshot, or null
$image = MDStaff::providers()->image($providerId);

if ($image !== null) {
    Storage::put("providers/{$image->safeUid()}.{$image->extension()}", $image->contents);
}
```

The provider sources and their preset fields, filters, and sorts live in `ProviderQueries`. `QuerySource::primaryKey()` returns the identifying field of each source's records.

### Lookups and facilities

```php
use ChrisReedIO\MDStaff\Enums\LookUpType;

MDStaff::lookUps()->list(LookUpType::Specialty)->all();
MDStaff::facilities()->list();
```

### Custom queries

Anything in the [MDStaff Query API](https://github.com/chrisreedio/mdstaff-docs/tree/main/asm_docs/query) can be sent directly:

```php
use ChrisReedIO\MDStaff\Enums\QuerySource;
use ChrisReedIO\MDStaff\MDStaffConnector;
use ChrisReedIO\MDStaff\Requests\QueryRequest;

$records = MDStaffConnector::forFacility($facilityUid)
    ->paginate(new QueryRequest(
        source: QuerySource::ReferenceSource,
        fields: ['ReferenceSourceID', 'Name', 'City', 'State'],
        filter: ['ReferenceType' => ['Medical Education']],
    ))
    ->setPerPageLimit(2000)
    ->collect();
```

Query responses are cached for `MDSTAFF_QUERY_CACHE_TTL` seconds. Call `->disableCaching()` on a request to bypass the cache.

## Rate limits

MDStaff limits requests per account. The connector throws `Saloon\RateLimitPlugin\Exceptions\RateLimitReachedException` once `MDSTAFF_REQUESTS_PER_MINUTE` is reached; the limit is stored in the default cache store. Token requests count toward it.

Background work can reserve from a smaller budget so interactive requests keep headroom. It throws `RequestBudgetExceededException`, whose `releaseInSeconds` suits `$this->release()` in a queued job:

```php
use ChrisReedIO\MDStaff\Support\RequestBudget;

app(RequestBudget::class)->reserve();
```

## Testing

```bash
composer test
```

Fake MDStaff in your application tests with Saloon's `MockClient::global([...])`.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Chris Reed](https://github.com/chrisreedio)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
