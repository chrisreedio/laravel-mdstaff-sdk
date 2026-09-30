<?php

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('runs a read-only MDStaff smoke test', function () {
    $mockClient = MockClient::global([
        MockResponse::make([
            'access_token' => 'access-token',
            'expires_in' => 3600,
        ]),
        MockResponse::make([
            ['ProviderID' => 'provider-id', 'NPI' => '1234567890'],
        ]),
    ]);

    $this->artisan('mdstaff:test')
        ->expectsOutputToContain('MDStaff integration test passed.')
        ->expectsOutputToContain('Demographic')
        ->expectsOutputToContain('ProviderID, NPI')
        ->doesntExpectOutputToContain('provider-id')
        ->doesntExpectOutputToContain('1234567890')
        ->assertSuccessful();

    expect($mockClient->getLastPendingRequest()?->body()?->all())->toBe([
        'source' => 'Demographic',
        'fields' => ['ProviderID', 'NPI'],
        'page' => 1,
        'resultsperpage' => 1,
    ]);
});

it('passes when MDStaff returns no demographic records', function () {
    MockClient::global([
        MockResponse::make([
            'access_token' => 'access-token',
            'expires_in' => 3600,
        ]),
        MockResponse::make([]),
    ]);

    $this->artisan('mdstaff:test')
        ->expectsOutputToContain('MDStaff integration test passed.')
        ->expectsOutputToContain('none (empty response)')
        ->assertSuccessful();
});

it('fails when MDStaff cannot authenticate', function () {
    MockClient::global([
        MockResponse::make(['message' => 'Unauthorized'], 401),
    ]);

    $this->artisan('mdstaff:test')
        ->expectsOutputToContain('MDStaff integration test failed.')
        ->assertFailed();
});

it('fails when the facility is not configured', function () {
    config()->set('mdstaff-sdk.facility_id', null);

    $this->artisan('mdstaff:test')
        ->expectsOutputToContain('MDStaff facility ID is not configured.')
        ->assertFailed();
});
