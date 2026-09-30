<?php

use ChrisReedIO\MDStaff\Enums\LookUpType;
use ChrisReedIO\MDStaff\Facades\MDStaff;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('lists facilities', function () {
    cacheFacilityAccessToken();
    MockClient::global([
        MockResponse::make([
            ['Uid' => 'facility-id', 'Code' => 'FACILITY', 'Name' => 'Facility'],
            'ignored',
        ]),
    ]);

    expect(MDStaff::facilities()->list())->toBe([
        ['Uid' => 'facility-id', 'Code' => 'FACILITY', 'Name' => 'Facility'],
    ]);
});

it('throws when listing facilities fails', function () {
    cacheFacilityAccessToken();
    MockClient::global([MockResponse::make(['message' => 'Forbidden'], 403)]);

    expect(fn () => MDStaff::facilities()->list())->toThrow(RequestException::class);
});

it('rejects facility responses that are not JSON arrays', function () {
    cacheFacilityAccessToken();
    MockClient::global([MockResponse::make(['Uid' => 'facility-id'])]);

    expect(fn () => MDStaff::facilities()->list())
        ->toThrow(UnexpectedValueException::class, 'MDStaff facility responses must contain a JSON array.');
});

it('lists lookups of one type', function (LookUpType|string $type, string $expectedType) {
    $mockClient = MockClient::global([
        MockResponse::make([
            ['LookUpID' => 'lookup-1', 'Description' => 'First'],
            ['LookUpID' => 'lookup-2', 'Description' => 'Second'],
        ]),
    ]);

    $lookUps = facilityConnector()->lookUps()->list($type)->all();
    $body = $mockClient->getLastPendingRequest()?->body()?->all();

    expect(array_column($lookUps, 'LookUpID'))->toBe(['lookup-1', 'lookup-2'])
        ->and($body)->toBe([
            'source' => 'LookUp',
            'fields' => ['LookUpID', 'Code', 'Description', 'LookUpType', 'Archived'],
            'filter' => ['LookUpType' => $expectedType],
            'page' => 1,
            'resultsperpage' => 2000,
        ]);
})->with([
    'enum' => [LookUpType::ResidencyTitle, 'ReferenceStatus'],
    'string' => ['Degree', 'Degree'],
]);
