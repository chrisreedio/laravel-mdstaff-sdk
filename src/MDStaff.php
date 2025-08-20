<?php

namespace ChrisReedIO\MDStaff;

use ChrisReedIO\MDStaff\Resources\Facilities;
use Saloon\Helpers\OAuth2\OAuthConfig;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\OAuth2\ClientCredentialsGrant;

class MDStaff extends Connector
{
    use ClientCredentialsGrant;

    protected string $accountCode;

    public function __construct(protected ?string $accessToken = null)
    {
        $this->accountCode = config('mdstaff-sdk.account_code');

        if ($this->accessToken) {
            $this->authenticate(new TokenAuthenticator($this->accessToken));
        }
    }

    public function resolveBaseUrl(): string
    {
        $baseUrl = config('mdstaff-sdk.base_url');

        return "https://{$this->accountCode}.{$baseUrl}";
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    protected function defaultOauthConfig(): OAuthConfig
    {
        $authType = config('mdstaff-sdk.auth.default');
        if ($authType === 'basic') {
            $username = config('mdstaff-sdk.auth.basic.username');
            $password = config('mdstaff-sdk.auth.basic.password');
        } else {
            $username = config('mdstaff-sdk.auth.oauth.client_id');
            $password = config('mdstaff-sdk.auth.oauth.client_secret');
        }

        if ($username === null || $password === null) {
            throw new \Exception('MDStaff Credentials are not set! Please check your .env file.');
        }

        return OAuthConfig::make()
            ->setClientId($username)
            ->setClientSecret($password)
            // ->setDefaultScopes([$scope])
            ->setTokenEndpoint('/api/tokens');
    }

    public function facilities(): Facilities
    {
        return new Facilities($this);
    }

    public function buildScope(string $facilityUid): string
    {
        return $this->accountCode.'/'.$facilityUid;
    }
}
