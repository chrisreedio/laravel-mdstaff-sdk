<?php

use ChrisReedIO\MDStaff\Enums\QuerySource;
use ChrisReedIO\MDStaff\Facades\MDStaff;
use ChrisReedIO\MDStaff\MDStaffConnector;
use ChrisReedIO\MDStaff\Requests\GetFacilitiesRequest;
use ChrisReedIO\MDStaff\Requests\QueryRequest;
use Illuminate\Support\Facades\Cache;
use Saloon\Enums\Method;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\RateLimitPlugin\Exceptions\RateLimitReachedException;

it('builds documented query requests', function () {
    $mockClient = new MockClient([
        MockResponse::make([]),
    ]);
    $connector = new MDStaffConnector('access-token');
    $connector->withMockClient($mockClient);

    $connector->send(new QueryRequest(
        source: QuerySource::LookUp,
        fields: ['LookUpID', 'Description'],
        filter: ['LookUpType' => 'Degree'],
        sort: [['Description' => 'asc']],
        settings: ['IncludeApplicants' => true],
        dynamicFilterParameters: ['ReportID' => 'report-id'],
        counts: true,
    ));

    $pendingRequest = $mockClient->getLastPendingRequest();

    expect($pendingRequest)
        ->not->toBeNull()
        ->and($pendingRequest->getMethod())->toBe(Method::POST)
        ->and($pendingRequest->getUrl())->toBe('https://example.api.asm-cloud.com/api/example/query')
        ->and($pendingRequest->headers()->get('Authorization'))->toBe('Bearer access-token')
        ->and($pendingRequest->body()?->all())->toBe([
            'source' => 'LookUp',
            'fields' => ['LookUpID', 'Description'],
            'filter' => ['LookUpType' => 'Degree'],
            'sort' => [['Description' => 'asc']],
            'settings' => ['IncludeApplicants' => true],
            'dynamicFilterParameters' => ['ReportID' => 'report-id'],
            'counts' => true,
        ]);
});

it('omits empty optional query properties', function () {
    $mockClient = new MockClient([MockResponse::make([])]);
    $connector = new MDStaffConnector('access-token');
    $connector->withMockClient($mockClient);

    $connector->send(new QueryRequest(source: 'CustomSource'));

    expect($mockClient->getLastPendingRequest()?->body()?->all())->toBe([
        'source' => 'CustomSource',
        'fields' => ['*'],
    ]);
});

it('caches identical query requests without sharing responses across payloads', function () {
    $mockClient = new MockClient([
        MockResponse::make([['ProviderID' => 'first-provider']]),
        MockResponse::make([['ProviderID' => 'second-provider']]),
    ]);
    $connector = new MDStaffConnector('access-token');
    $connector->withMockClient($mockClient);

    $firstResponse = $connector->send(new QueryRequest(
        source: QuerySource::Demographic,
        fields: ['ProviderID'],
    ));
    $cachedResponse = $connector->send(new QueryRequest(
        source: QuerySource::Demographic,
        fields: ['ProviderID'],
    ));
    $differentResponse = $connector->send(new QueryRequest(
        source: QuerySource::Demographic,
        fields: ['ProviderID', 'NPI'],
    ));

    expect($firstResponse->isCached())->toBeFalse()
        ->and($cachedResponse->isCached())->toBeTrue()
        ->and($cachedResponse->json())->toBe([['ProviderID' => 'first-provider']])
        ->and($differentResponse->isCached())->toBeFalse()
        ->and($differentResponse->json())->toBe([['ProviderID' => 'second-provider']])
        ->and($mockClient->getRecordedResponses())->toHaveCount(2);
});

it('limits MDStaff to the configured outbound requests per minute', function (?int $configured, int $expected) {
    if ($configured !== null) {
        config()->set('mdstaff-sdk.rate_limits.requests_per_minute', $configured);
    }

    $mockClient = new MockClient([MockResponse::make([])]);
    $connector = new MDStaffConnector('access-token');
    $connector->withMockClient($mockClient);
    $limit = $connector->getLimits()[0];

    Cache::put($limit->getName(), json_encode([
        'timestamp' => now()->addMinute()->getTimestamp(),
        'hits' => $expected,
        'allow' => $expected,
    ], JSON_THROW_ON_ERROR), 60);

    expect(fn () => $connector->send(new QueryRequest(
        source: QuerySource::Demographic,
        filter: ['ProviderID' => 'provider'],
    )))->toThrow(RateLimitReachedException::class)
        ->and($limit->getAllow())->toBe($expected)
        ->and($limit->getReleaseInSeconds())->toBe(60)
        ->and($mockClient->getRecordedResponses())->toHaveCount(0);
})->with([
    'default of ten' => [null, 10],
    'configured five' => [5, 5],
]);

it('paginates query response arrays until the final partial page', function () {
    $mockClient = new MockClient([
        MockResponse::make([
            ['LookUpID' => '1'],
            ['LookUpID' => '2'],
        ]),
        MockResponse::make([
            ['LookUpID' => '3'],
            ['LookUpID' => '4'],
        ]),
        MockResponse::make([
            ['LookUpID' => '5'],
        ]),
    ]);
    $connector = new MDStaffConnector('access-token');
    $connector->withMockClient($mockClient);

    $items = iterator_to_array(
        $connector
            ->paginate(new QueryRequest(source: QuerySource::LookUp))
            ->setPerPageLimit(2)
            ->items(),
        preserve_keys: false,
    );
    $cachedItems = iterator_to_array(
        $connector
            ->paginate(new QueryRequest(source: QuerySource::LookUp))
            ->setPerPageLimit(2)
            ->items(),
        preserve_keys: false,
    );

    $sentBodies = array_map(
        fn ($response) => $response->getPendingRequest()->body()?->all(),
        $mockClient->getRecordedResponses(),
    );

    expect(array_column($items, 'LookUpID'))->toBe(['1', '2', '3', '4', '5'])
        ->and(array_column($cachedItems, 'LookUpID'))->toBe(['1', '2', '3', '4', '5'])
        ->and(array_column($sentBodies, 'page'))->toBe([1, 2, 3])
        ->and(array_column($sentBodies, 'resultsperpage'))->toBe([2, 2, 2]);
});

it('rejects query responses that are not JSON arrays', function () {
    $connector = new MDStaffConnector('access-token');
    $connector->withMockClient(new MockClient([
        MockResponse::make(['message' => 'Not a list']),
    ]));

    expect(fn () => iterator_to_array(
        $connector->paginate(new QueryRequest(source: QuerySource::LookUp))->items(),
    ))->toThrow(UnexpectedValueException::class, 'MDStaff query responses must contain a JSON array.');
});

it('only paginates query requests', function () {
    expect(fn () => (new MDStaffConnector('access-token'))->paginate(new GetFacilitiesRequest))
        ->toThrow(InvalidArgumentException::class, 'Only MDStaff query requests can be paginated.');
});

it('rejects invalid paginator page limits', function (int $perPageLimit) {
    $paginator = (new MDStaffConnector('access-token'))
        ->paginate(new QueryRequest(source: QuerySource::LookUp));

    expect(fn () => $paginator->setPerPageLimit($perPageLimit))
        ->toThrow(InvalidArgumentException::class, 'MDStaff results per page must be between 1 and 2000.');
})->with([
    'zero results per page' => 0,
    'more than 2000 results per page' => 2001,
]);

it('rejects invalid paginator start pages', function () {
    $paginator = (new MDStaffConnector('access-token'))
        ->paginate(new QueryRequest(source: QuerySource::LookUp));

    expect(fn () => $paginator->setStartPage(0))
        ->toThrow(InvalidArgumentException::class, 'MDStaff page must be at least 1.');
});

it('requests facility scoped access tokens', function () {
    $mockClient = new MockClient([
        MockResponse::make([
            'access_token' => 'access-token',
            'expires_in' => 3600,
        ]),
    ]);
    $connector = new MDStaffConnector;
    $connector->withMockClient($mockClient);

    $response = $connector->getAccessToken(
        [$connector->buildScope('facility-id')],
        returnResponse: true,
    );

    $pendingRequest = $response->getPendingRequest();

    expect($pendingRequest->getMethod())->toBe(Method::POST)
        ->and($pendingRequest->getUrl())->toBe('https://example.api.asm-cloud.com/api/tokens')
        ->and($pendingRequest->body()?->all())->toBe([
            'grant_type' => 'client_credentials',
            'client_id' => 'username',
            'client_secret' => 'password',
            'scope' => 'example/facility-id',
        ]);
});

it('sends OAuth client credentials when configured', function () {
    config()->set('mdstaff-sdk.auth.default', 'oauth');
    config()->set('mdstaff-sdk.auth.oauth.client_id', 'client-id');
    config()->set('mdstaff-sdk.auth.oauth.client_secret', 'client-secret');

    $connector = new MDStaffConnector;
    $connector->withMockClient(new MockClient([
        MockResponse::make(['access_token' => 'access-token', 'expires_in' => 3600]),
    ]));

    $body = $connector->getAccessToken(returnResponse: true)->getPendingRequest()->body()?->all();

    expect($body['client_id'] ?? null)->toBe('client-id')
        ->and($body['client_secret'] ?? null)->toBe('client-secret');
});

it('resolves and caches facility access tokens when requests are sent', function () {
    $mockClient = MockClient::global([
        MockResponse::make([
            'access_token' => 'cached-access-token',
            'expires_in' => 3600,
        ]),
        MockResponse::make([]),
        MockResponse::make([]),
    ]);

    $connector = MDStaffConnector::forConfiguredFacility();

    expect($mockClient->getRecordedResponses())->toHaveCount(0);

    $connector->send((new QueryRequest(source: QuerySource::Demographic))->disableCaching());
    MDStaffConnector::forConfiguredFacility()->send(
        (new QueryRequest(source: QuerySource::Demographic))->disableCaching(),
    );

    $responses = $mockClient->getRecordedResponses();

    expect($responses)->toHaveCount(3)
        ->and($responses[0]->getPendingRequest()->getUrl())
        ->toBe('https://example.api.asm-cloud.com/api/tokens')
        ->and($responses[0]->getPendingRequest()->headers()->get('Authorization'))->toBeNull()
        ->and($responses[0]->getPendingRequest()->body()?->all()['scope'] ?? null)->toBe('example/facility-id')
        ->and($responses[1]->getPendingRequest()->headers()->get('Authorization'))
        ->toBe('Bearer cached-access-token')
        ->and($responses[2]->getPendingRequest()->headers()->get('Authorization'))
        ->toBe('Bearer cached-access-token');
});

it('uses an already cached facility access token without requesting a new one', function () {
    $mockClient = MockClient::global([MockResponse::make([])]);

    facilityConnector()->send((new QueryRequest(source: QuerySource::Demographic))->disableCaching());

    expect($mockClient->getRecordedResponses())->toHaveCount(1)
        ->and($mockClient->getLastPendingRequest()?->headers()->get('Authorization'))->toBe('Bearer access-token');
});

it('surfaces authentication failures when a request is sent', function () {
    MockClient::global([
        MockResponse::make(['message' => 'Unauthorized'], 401),
    ]);

    expect(fn () => MDStaffConnector::forConfiguredFacility()->send(
        (new QueryRequest(source: QuerySource::Demographic))->disableCaching(),
    ))->toThrow(RequestException::class);

    expect(Cache::get('mdstaff:access-token:'.hash('sha256', 'example/facility-id')))->toBeNull();
});

it('builds the facilities request', function () {
    $mockClient = new MockClient([
        MockResponse::make([]),
    ]);
    $connector = new MDStaffConnector;
    $connector->withMockClient($mockClient);

    $connector->send(new GetFacilitiesRequest);

    $pendingRequest = $mockClient->getLastPendingRequest();

    expect($pendingRequest)
        ->not->toBeNull()
        ->and($pendingRequest->getMethod())->toBe(Method::GET)
        ->and($pendingRequest->getUrl())->toBe('https://example.api.asm-cloud.com/api/example/facilities');
});

it('requires the account code, facility, and credentials to be configured', function (string $key, callable $callback, string $message) {
    config()->set("mdstaff-sdk.{$key}", null);

    expect($callback)->toThrow(InvalidArgumentException::class, $message);
})->with([
    'account code' => ['account_code', fn () => new MDStaffConnector, 'MDStaff account code is not configured.'],
    'facility' => ['facility_id', fn () => MDStaffConnector::forConfiguredFacility(), 'MDStaff facility ID is not configured.'],
    'credentials' => ['auth.basic.password', fn () => (new MDStaffConnector)->getAccessToken(), 'MDStaff credentials are not configured.'],
]);

it('rejects an empty facility ID', function () {
    expect(fn () => MDStaffConnector::forFacility(''))
        ->toThrow(InvalidArgumentException::class, 'MDStaff facility ID cannot be empty.');
});

it('resolves the facade to a connector for the configured facility', function () {
    expect(MDStaff::getFacadeRoot())->toBeInstanceOf(MDStaffConnector::class)
        ->and(MDStaff::getFacadeRoot()->facilityUid())->toBe('facility-id');
});

it('resolves an unscoped connector when no facility is configured', function () {
    config()->set('mdstaff-sdk.facility_id', null);

    expect(app(MDStaffConnector::class)->facilityUid())->toBeNull();
});
