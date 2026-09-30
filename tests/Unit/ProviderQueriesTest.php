<?php

use ChrisReedIO\MDStaff\Enums\QuerySource;
use ChrisReedIO\MDStaff\Queries\ProviderQueries;

it('defines the provider sources in dependency order', function () {
    expect(ProviderQueries::sources())->toBe([
        QuerySource::Demographic,
        QuerySource::Appointment,
        QuerySource::Address,
        QuerySource::Reference,
        QuerySource::BoardCertification,
        QuerySource::ProviderFile,
    ]);
});

it('requests the primary key and provider ID for every provider source', function (QuerySource $source) {
    expect(ProviderQueries::fields($source))
        ->toContain($source->primaryKey())
        ->toContain('ProviderID');
})->with(fn () => ProviderQueries::sources());

it('has no preset fields for non provider sources', function () {
    expect(ProviderQueries::fields(QuerySource::LookUp))->toBe([]);
});

it('builds provider source filters', function (QuerySource $source, ?string $providerId, array $expected) {
    expect(ProviderQueries::filter($source, $providerId))->toBe($expected);
})->with([
    'demographic for one provider' => [QuerySource::Demographic, 'provider-1', ['ProviderID' => 'provider-1']],
    'demographic for everyone' => [QuerySource::Demographic, null, []],
    'board certifications in use' => [QuerySource::BoardCertification, null, ['InUse' => true]],
    'image provider files' => [QuerySource::ProviderFile, null, [
        'InUse' => true,
        'FileDescription' => [
            ['type' => 'search', 'values' => ['%.jpg']],
            ['type' => 'search', 'values' => ['%.jpeg']],
            ['type' => 'search', 'values' => ['%.png']],
        ],
    ]],
]);

it('only sorts provider files', function () {
    expect(ProviderQueries::sort(QuerySource::ProviderFile))->toBe([['InUse' => 'desc'], ['DateUploaded' => 'desc']])
        ->and(ProviderQueries::sort(QuerySource::Demographic))->toBe([]);
});

it('builds an inclusive date-only LastUpdated range', function () {
    expect(ProviderQueries::lastUpdatedBetween(
        new DateTimeImmutable('2026-01-05 23:59:00'),
        new DateTimeImmutable('2026-02-10 00:01:00'),
    ))->toBe([
        'LastUpdated' => [['type' => 'between', 'values' => ['01/05/2026', '02/10/2026']]],
    ]);
});

it('knows the primary key of each source', function (QuerySource $source, ?string $primaryKey) {
    expect($source->primaryKey())->toBe($primaryKey);
})->with([
    [QuerySource::Demographic, 'ProviderID'],
    [QuerySource::ProviderFile, 'Uid'],
    [QuerySource::LookUp, 'LookUpID'],
    [QuerySource::Education, 'ReferenceID'],
    [QuerySource::AdHoc, null],
]);
