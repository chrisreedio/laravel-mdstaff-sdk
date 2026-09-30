<?php

declare(strict_types=1);

namespace ChrisReedIO\MDStaff\Requests;

use ChrisReedIO\MDStaff\Enums\QuerySource;
use ChrisReedIO\MDStaff\Support\MDStaffConfig;
use Illuminate\Support\Facades\Cache;
use Saloon\CachePlugin\Contracts\Cacheable;
use Saloon\CachePlugin\Contracts\Driver;
use Saloon\CachePlugin\Drivers\LaravelCacheDriver;
use Saloon\CachePlugin\Helpers\CacheKeyHelper;
use Saloon\CachePlugin\Traits\HasCaching;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use Saloon\Traits\Body\HasJsonBody;

class QueryRequest extends Request implements Cacheable, HasBody, Paginatable
{
    use HasCaching;
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<int, string|array{name: string, alias?: string, type?: string}>  $fields
     * @param  array<string, mixed>  $filter
     * @param  array<int, array<string, 'asc'|'desc'|'ASC'|'DESC'>>  $sort
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $dynamicFilterParameters
     */
    public function __construct(
        protected QuerySource|string $source,
        protected array $fields = ['*'],
        protected array $filter = [],
        protected array $sort = [],
        protected array $settings = [],
        protected array $dynamicFilterParameters = [],
        protected bool $counts = false,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/'.MDStaffConfig::accountCode().'/query';
    }

    public function resolveCacheDriver(): Driver
    {
        return new LaravelCacheDriver(Cache::store());
    }

    public function cacheExpiryInSeconds(): int
    {
        return MDStaffConfig::queryCacheTtl();
    }

    /**
     * @return array<Method>
     */
    protected function getCacheableMethods(): array
    {
        return [Method::POST];
    }

    protected function cacheKey(PendingRequest $pendingRequest): string
    {
        return json_encode([
            'request' => CacheKeyHelper::create($pendingRequest),
            'body' => $pendingRequest->body()?->all(),
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        $body = [
            'source' => $this->source instanceof QuerySource ? $this->source->value : $this->source,
            'fields' => $this->fields,
        ];

        if ($this->filter !== []) {
            $body['filter'] = $this->filter;
        }

        if ($this->sort !== []) {
            $body['sort'] = $this->sort;
        }

        if ($this->settings !== []) {
            $body['settings'] = $this->settings;
        }

        if ($this->dynamicFilterParameters !== []) {
            $body['dynamicFilterParameters'] = $this->dynamicFilterParameters;
        }

        if ($this->counts) {
            $body['counts'] = true;
        }

        return $body;
    }
}
