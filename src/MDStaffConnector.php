<?php

declare(strict_types=1);

namespace ChrisReedIO\MDStaff;

use ChrisReedIO\MDStaff\Pagination\QueryPaginator;
use ChrisReedIO\MDStaff\Requests\QueryRequest;
use ChrisReedIO\MDStaff\Resources\Facilities;
use ChrisReedIO\MDStaff\Resources\LookUps;
use ChrisReedIO\MDStaff\Resources\Providers;
use ChrisReedIO\MDStaff\Support\MDStaffConfig;
use DateTimeImmutable;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Saloon\Contracts\Authenticator;
use Saloon\Helpers\OAuth2\OAuthConfig;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\HasPagination;
use Saloon\PaginationPlugin\Paginator;
use Saloon\RateLimitPlugin\Contracts\RateLimitStore;
use Saloon\RateLimitPlugin\Limit;
use Saloon\RateLimitPlugin\Stores\LaravelCacheStore;
use Saloon\RateLimitPlugin\Traits\HasRateLimits;
use Saloon\Traits\OAuth2\ClientCredentialsGrant;
use Saloon\Traits\Plugins\AcceptsJson;

class MDStaffConnector extends Connector implements HasPagination
{
    use AcceptsJson;
    use ClientCredentialsGrant;
    use HasRateLimits;

    private const int ACCESS_TOKEN_EXPIRY_BUFFER_SECONDS = 60;

    protected string $accountCode;

    protected ?string $facilityUid = null;

    private bool $isResolvingAccessToken = false;

    public function __construct(protected ?string $accessToken = null)
    {
        $this->accountCode = MDStaffConfig::accountCode();

        if ($this->accessToken !== null && $this->accessToken !== '') {
            $this->authenticate(new TokenAuthenticator($this->accessToken));
        }
    }

    public static function forConfiguredFacility(): self
    {
        return self::forFacility(MDStaffConfig::facilityId());
    }

    /**
     * Create a connector whose requests are authenticated with a cached,
     * facility-scoped access token that is resolved when each request is sent.
     */
    public static function forFacility(string $facilityUid): self
    {
        if ($facilityUid === '') {
            throw new InvalidArgumentException('MDStaff facility ID cannot be empty.');
        }

        $connector = new self;
        $connector->facilityUid = $facilityUid;

        return $connector;
    }

    public function providers(): Providers
    {
        return new Providers($this);
    }

    public function facilities(): Facilities
    {
        return new Facilities($this);
    }

    public function lookUps(): LookUps
    {
        return new LookUps($this);
    }

    public function accountCode(): string
    {
        return $this->accountCode;
    }

    public function facilityUid(): ?string
    {
        return $this->facilityUid;
    }

    public function resolveBaseUrl(): string
    {
        return "https://{$this->accountCode}.".MDStaffConfig::baseUrl();
    }

    protected function defaultOauthConfig(): OAuthConfig
    {
        $credentials = MDStaffConfig::credentials();

        return OAuthConfig::make()
            ->setClientId($credentials['client_id'])
            ->setClientSecret($credentials['client_secret'])
            ->setTokenEndpoint('/api/tokens');
    }

    protected function defaultAuth(): ?Authenticator
    {
        if ($this->facilityUid === null || $this->isResolvingAccessToken) {
            return null;
        }

        return new TokenAuthenticator($this->resolveFacilityAccessToken($this->facilityUid));
    }

    public function buildScope(string $facilityUid): string
    {
        return "{$this->accountCode}/{$facilityUid}";
    }

    private function resolveFacilityAccessToken(string $facilityUid): string
    {
        $cacheKey = $this->accessTokenCacheKey($facilityUid);
        $cachedAccessToken = Cache::get($cacheKey);

        if (is_string($cachedAccessToken) && $cachedAccessToken !== '') {
            return $cachedAccessToken;
        }

        return Cache::lock("{$cacheKey}:lock", 30)->block(10, function () use ($cacheKey, $facilityUid): string {
            $cachedAccessToken = Cache::get($cacheKey);

            if (is_string($cachedAccessToken) && $cachedAccessToken !== '') {
                return $cachedAccessToken;
            }

            $this->isResolvingAccessToken = true;

            try {
                $authenticator = $this->getAccessToken([$this->buildScope($facilityUid)]);
            } finally {
                $this->isResolvingAccessToken = false;
            }

            $accessToken = $authenticator->getAccessToken();
            $expiresAt = $authenticator->getExpiresAt();

            if ($expiresAt instanceof DateTimeImmutable) {
                $cacheExpiresAt = $expiresAt->modify('-'.self::ACCESS_TOKEN_EXPIRY_BUFFER_SECONDS.' seconds');

                if ($cacheExpiresAt > new DateTimeImmutable) {
                    Cache::put($cacheKey, $accessToken, $cacheExpiresAt);
                }
            }

            return $accessToken;
        });
    }

    private function accessTokenCacheKey(string $facilityUid): string
    {
        return 'mdstaff:access-token:'.hash('sha256', $this->buildScope($facilityUid));
    }

    public function paginate(Request $request): Paginator
    {
        if (! $request instanceof QueryRequest) {
            throw new InvalidArgumentException('Only MDStaff query requests can be paginated.');
        }

        return new QueryPaginator($this, $request);
    }

    /**
     * @return array<Limit>
     */
    protected function resolveLimits(): array
    {
        return [
            (new Limit(
                allow: MDStaffConfig::requestsPerMinute(),
                responseHandler: static function (Response $response, Limit $limit): void {
                    if (! $response->getPendingRequest()->hasFakeResponse()) {
                        $limit->hit();
                    }
                },
            ))
                ->everyMinute()
                ->name('requests'),
        ];
    }

    protected function resolveRateLimitStore(): RateLimitStore
    {
        return new LaravelCacheStore(Cache::store());
    }

    protected function getLimiterPrefix(): string
    {
        return "mdstaff:{$this->accountCode}";
    }
}
