<?php

use ChrisReedIO\MDStaff\Data\ProviderImage;
use ChrisReedIO\MDStaff\Enums\QuerySource;
use ChrisReedIO\MDStaff\Exceptions\InvalidProviderImageException;
use ChrisReedIO\MDStaff\Queries\ProviderQueries;
use Illuminate\Support\LazyCollection;
use Saloon\Enums\Method;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('searches provider names with one narrow demographic query', function () {
    $mockClient = MockClient::global([
        MockResponse::make([[
            'ProviderID' => 'provider-1',
            'FirstName' => 'Jane',
            'LastName' => 'Doe',
            'FormattedNameWithDegree' => 'Jane Doe, MD',
            'NPI' => '1234567890',
        ], [
            'ProviderID' => '',
            'FirstName' => 'Ignored',
        ]]),
    ]);

    $providers = facilityConnector()->providers()->search('Jane Doe');
    $body = $mockClient->getLastPendingRequest()?->body()?->all();

    expect($providers)->toBe([[
        'ProviderID' => 'provider-1',
        'FirstName' => 'Jane',
        'LastName' => 'Doe',
        'FormattedNameWithDegree' => 'Jane Doe, MD',
        'NPI' => '1234567890',
    ]])
        ->and($body)->toBe([
            'source' => 'Demographic',
            'fields' => ['ProviderID', 'FirstName', 'LastName', 'FormattedNameWithDegree', 'NPI'],
            'filter' => [
                'FormattedNameWithDegree' => [[
                    'type' => 'search',
                    'values' => ['%Jane Doe%'],
                ], [
                    'type' => 'search',
                    'values' => ['%Doe, Jane%'],
                ]],
            ],
            'sort' => [['FormattedNameWithDegree' => 'asc']],
            'settings' => ProviderQueries::settings(),
            'page' => 1,
            'resultsperpage' => 10,
        ])
        ->and($mockClient->getRecordedResponses())->toHaveCount(1);
});

it('searches a single name term without a reversed name filter', function () {
    $mockClient = MockClient::global([MockResponse::make([])]);

    expect(facilityConnector()->providers()->search('  Doe%  ', limit: 5))->toBe([]);

    $body = $mockClient->getLastPendingRequest()?->body()?->all();

    expect($body['filter'] ?? null)->toBe([
        'FormattedNameWithDegree' => [[
            'type' => 'search',
            'values' => ['%Doe%'],
        ]],
    ])->and($body['resultsperpage'] ?? null)->toBe(5);
});

it('rejects provider name searches shorter than three characters', function (string $name) {
    $mockClient = MockClient::global([]);

    expect(fn () => facilityConnector()->providers()->search($name))
        ->toThrow(InvalidArgumentException::class, 'Enter at least three characters');

    expect($mockClient->getRecordedResponses())->toHaveCount(0);
})->with([
    'two characters' => 'Jo',
    'wildcards only' => '%%%',
    'whitespace' => '    ',
]);

it('finds a provider demographic by NPI', function () {
    $mockClient = MockClient::global([
        MockResponse::make([['ProviderID' => 'provider-1', 'NPI' => '1234567890']]),
    ]);

    $provider = facilityConnector()->providers()->findByNpi(' 1234567890 ');
    $body = $mockClient->getLastPendingRequest()?->body()?->all();

    expect($provider)->toBe(['ProviderID' => 'provider-1', 'NPI' => '1234567890'])
        ->and($body)->toBe([
            'source' => 'Demographic',
            'fields' => ProviderQueries::fields(QuerySource::Demographic),
            'filter' => ['NPI' => '1234567890'],
            'settings' => ProviderQueries::settings(),
            'page' => 1,
            'resultsperpage' => 1,
        ]);
});

it('finds a provider demographic by provider ID with custom fields', function () {
    $mockClient = MockClient::global([
        MockResponse::make([['ProviderID' => 'provider-1']]),
    ]);

    $provider = facilityConnector()->providers()->find('provider-1', ['ProviderID']);
    $body = $mockClient->getLastPendingRequest()?->body()?->all();

    expect($provider)->toBe(['ProviderID' => 'provider-1'])
        ->and($body['fields'] ?? null)->toBe(['ProviderID'])
        ->and($body['filter'] ?? null)->toBe(['ProviderID' => 'provider-1']);
});

it('returns null when no provider matches', function () {
    MockClient::global([MockResponse::make([])]);

    expect(facilityConnector()->providers()->findByNpi('0000000000'))->toBeNull();
});

it('rejects a blank NPI lookup', function () {
    expect(fn () => facilityConnector()->providers()->findByNpi('  '))
        ->toThrow(InvalidArgumentException::class, 'An NPI is required');
});

it('lazily pages provider source records with preset and caller filters', function () {
    $mockClient = MockClient::global([
        MockResponse::make([
            ['AddressID' => 'address-1', 'ProviderID' => 'provider-1'],
            ['AddressID' => 'address-2', 'ProviderID' => 'provider-1'],
        ]),
        MockResponse::make([
            ['AddressID' => 'address-3', 'ProviderID' => 'provider-1'],
        ]),
    ]);

    $records = facilityConnector()->providers()->records(
        QuerySource::Address,
        providerId: 'provider-1',
        filter: ProviderQueries::lastUpdatedBetween(new DateTimeImmutable('2026-09-01'), new DateTimeImmutable('2026-09-30')),
        perPage: 2,
    );

    expect($records)->toBeInstanceOf(LazyCollection::class)
        ->and($mockClient->getRecordedResponses())->toHaveCount(0)
        ->and($records->pluck('AddressID')->all())->toBe(['address-1', 'address-2', 'address-3']);

    $body = $mockClient->getRecordedResponses()[0]->getPendingRequest()->body()?->all();

    expect($mockClient->getRecordedResponses())->toHaveCount(2)
        ->and($body['source'] ?? null)->toBe('Address')
        ->and($body['fields'] ?? null)->toBe(ProviderQueries::fields(QuerySource::Address))
        ->and($body['filter'] ?? null)->toBe([
            'ProviderID' => 'provider-1',
            'AddressType' => ['Primary', 'Office', 'Rural', 'ASC', 'PSA', 'AltOffice'],
            'InUse' => true,
            'LastUpdated' => [['type' => 'between', 'values' => ['09/01/2026', '09/30/2026']]],
        ])
        ->and($body['settings'] ?? null)->toBe(ProviderQueries::settings())
        ->and($body['resultsperpage'] ?? null)->toBe(2);
});

it('pages whole provider sources at the MDStaff maximum by default', function () {
    $mockClient = MockClient::global([MockResponse::make([])]);

    facilityConnector()->providers()->records(QuerySource::Demographic)->all();

    $body = $mockClient->getLastPendingRequest()?->body()?->all();

    expect($body['resultsperpage'] ?? null)->toBe(2000)
        ->and(array_key_exists('filter', $body))->toBeFalse();
});

it('uses the documented narrow and sorted ProviderFile query', function () {
    $mockClient = MockClient::global([
        MockResponse::make([[
            'Uid' => 'image-uid',
            'ProviderID' => 'provider-1',
            'FileDescription' => 'provider.jpg',
            'FileTypeID' => 'file-type',
            'InUse' => true,
            'DateUploaded' => '08/19/2026',
        ]]),
    ]);

    $file = facilityConnector()->providers()->latestImageFile('provider-1');
    $body = $mockClient->getLastPendingRequest()?->body()?->all();

    expect($file['Uid'] ?? null)->toBe('image-uid')
        ->and($body['source'] ?? null)->toBe('ProviderFile')
        ->and($body['fields'] ?? null)->toBe([
            'Uid', 'ProviderID', 'FileDescription', 'FileTypeID', 'InUse', 'DateUploaded',
        ])
        ->and($body['filter'] ?? null)->toBe([
            'ProviderID' => 'provider-1',
            'InUse' => true,
            'FileDescription' => ProviderQueries::imageFileDescriptionFilters(),
        ])
        ->and($body['sort'] ?? null)->toBe([
            ['InUse' => 'desc'],
            ['DateUploaded' => 'desc'],
        ])
        ->and($body['resultsperpage'] ?? null)->toBe(10)
        ->and($body['page'] ?? null)->toBe(1)
        ->and($mockClient->getRecordedResponses())->toHaveCount(1);
});

it('downloads JSON base64 and raw provider images', function (string $mimeType, string $contents, bool $base64Encoded, string $extension) {
    $mockClient = MockClient::global([
        MockResponse::make($base64Encoded ? json_encode(base64_encode($contents), JSON_THROW_ON_ERROR) : $contents),
    ]);

    $image = facilityConnector()->providers()->downloadImage('provider-1', 'image/uid');
    $request = $mockClient->getLastPendingRequest();

    expect($image)->toBeInstanceOf(ProviderImage::class)
        ->and($image->providerId)->toBe('provider-1')
        ->and($image->uid)->toBe('image/uid')
        ->and($image->safeUid())->toBe('image-uid')
        ->and($image->contents)->toBe($contents)
        ->and($image->mimeType)->toBe($mimeType)
        ->and($image->extension())->toBe($extension)
        ->and($image->size())->toBe(strlen($contents))
        ->and($request?->getMethod())->toBe(Method::POST)
        ->and($request?->getUrl())
        ->toBe('https://example.api.asm-cloud.com/api/example/providers/provider-1/providerfile/image%2Fuid/download');
})->with([
    'JSON base64 JPEG' => ['image/jpeg', fn () => jpegImage(), true, 'jpg'],
    'raw PNG' => ['image/png', fn () => pngImage(), false, 'png'],
]);

it('rejects invalid provider image downloads', function (string $response, string $message) {
    MockClient::global([MockResponse::make($response)]);

    expect(fn () => facilityConnector()->providers()->downloadImage('provider-1', 'image-uid'))
        ->toThrow(InvalidProviderImageException::class, $message);
})->with([
    'invalid base64' => [json_encode('not-base64!', JSON_THROW_ON_ERROR), 'invalid base64'],
    'empty body' => ['', 'empty provider image'],
    'unsupported file' => ['%PDF-1.4 invalid image', 'must be JPEG or PNG'],
    'truncated image' => [substr(jpegImage(), 0, 20), 'unreadable provider image'],
    'oversized file' => [json_encode(base64_encode(str_repeat('a', (3 * 1024 * 1024) + 1)), JSON_THROW_ON_ERROR), '3 MB or smaller'],
]);

it('throws when a provider image download fails', function () {
    MockClient::global([MockResponse::make(['message' => 'Not found'], 404)]);

    expect(fn () => facilityConnector()->providers()->downloadImage('provider-1', 'image-uid'))
        ->toThrow(RequestException::class);
});

it('rejects image downloads without identifiers', function () {
    expect(fn () => facilityConnector()->providers()->downloadImage('provider-1', ''))
        ->toThrow(InvalidArgumentException::class, 'MDStaff provider and file identifiers are required.');
});

it('finds and downloads the latest provider image', function () {
    $mockClient = MockClient::global([
        MockResponse::make([['Uid' => 'image-uid', 'ProviderID' => 'provider-1']]),
        MockResponse::make(pngImage()),
    ]);

    $image = facilityConnector()->providers()->image('provider-1');

    expect($image?->uid)->toBe('image-uid')
        ->and($image?->extension())->toBe('png')
        ->and($mockClient->getRecordedResponses())->toHaveCount(2);
});

it('returns no image without downloading when the provider has no image file', function () {
    $mockClient = MockClient::global([MockResponse::make([])]);

    expect(facilityConnector()->providers()->image('provider-1'))->toBeNull()
        ->and($mockClient->getRecordedResponses())->toHaveCount(1);
});
