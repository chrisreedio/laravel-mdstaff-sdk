<?php

declare(strict_types=1);

namespace ChrisReedIO\MDStaff\Resources;

use ChrisReedIO\MDStaff\Data\ProviderImage;
use ChrisReedIO\MDStaff\Enums\QuerySource;
use ChrisReedIO\MDStaff\Pagination\QueryPaginator;
use ChrisReedIO\MDStaff\Queries\ProviderQueries;
use ChrisReedIO\MDStaff\Requests\DownloadProviderFileRequest;
use ChrisReedIO\MDStaff\Requests\QueryRequest;
use Illuminate\Support\LazyCollection;
use InvalidArgumentException;

class Providers extends Resource
{
    /**
     * Search providers by name. Matches "First Last" and "Last, First" forms.
     *
     * @return array<int, array{ProviderID: string, FirstName: ?string, LastName: ?string, FormattedNameWithDegree: ?string, NPI: ?string}>
     */
    public function search(string $name, int $limit = 10): array
    {
        $name = trim(str_replace('%', '', $name));

        if (mb_strlen($name) < 3) {
            throw new InvalidArgumentException('Enter at least three characters when searching MDStaff providers.');
        }

        $searches = [[
            'type' => 'search',
            'values' => ["%{$name}%"],
        ]];
        $nameParts = preg_split('/[\s,]+/', $name, flags: PREG_SPLIT_NO_EMPTY);

        if (is_array($nameParts) && count($nameParts) > 1) {
            $searches[] = [
                'type' => 'search',
                'values' => ['%'.end($nameParts).', '.$nameParts[0].'%'],
            ];
        }

        $items = $this->firstPage(new QueryRequest(
            source: QuerySource::Demographic,
            fields: ['ProviderID', 'FirstName', 'LastName', 'FormattedNameWithDegree', 'NPI'],
            filter: ['FormattedNameWithDegree' => $searches],
            sort: [['FormattedNameWithDegree' => 'asc']],
            settings: ProviderQueries::settings(),
        ), $limit);

        return collect($items)
            ->filter(fn (mixed $item): bool => is_array($item) && self::nullableString($item['ProviderID'] ?? null) !== null)
            ->map(fn (array $item): array => [
                'ProviderID' => (string) self::nullableString($item['ProviderID']),
                'FirstName' => self::nullableString($item['FirstName'] ?? null),
                'LastName' => self::nullableString($item['LastName'] ?? null),
                'FormattedNameWithDegree' => self::nullableString($item['FormattedNameWithDegree'] ?? null),
                'NPI' => self::nullableString($item['NPI'] ?? null),
            ])
            ->values()
            ->all();
    }

    /**
     * Find a provider's Demographic record by MDStaff provider ID.
     *
     * @param  array<int, string>|null  $fields
     * @return array<string, mixed>|null
     */
    public function find(string $providerId, ?array $fields = null): ?array
    {
        return $this->firstDemographic(['ProviderID' => $providerId], $fields);
    }

    /**
     * Find a provider's Demographic record by NPI.
     *
     * @param  array<int, string>|null  $fields
     * @return array<string, mixed>|null
     */
    public function findByNpi(string $npi, ?array $fields = null): ?array
    {
        $npi = trim($npi);

        if ($npi === '') {
            throw new InvalidArgumentException('An NPI is required to find an MDStaff provider.');
        }

        return $this->firstDemographic(['NPI' => $npi], $fields);
    }

    /**
     * Lazily page through one provider-level source using the documented
     * field, filter, sort, and settings presets. Requests are only sent as
     * the collection is iterated.
     *
     * @param  array<string, mixed>  $filter  Merged over the preset filter.
     * @param  array<int, string>|null  $fields
     * @return LazyCollection<int, array<mixed>>
     */
    public function records(
        QuerySource $source,
        ?string $providerId = null,
        array $filter = [],
        ?array $fields = null,
        int $perPage = QueryPaginator::MAX_PER_PAGE_LIMIT,
    ): LazyCollection {
        $fields ??= ProviderQueries::fields($source);

        return $this->connector
            ->paginate(new QueryRequest(
                source: $source,
                fields: $fields === [] ? ['*'] : $fields,
                filter: array_merge(ProviderQueries::filter($source, $providerId), $filter),
                sort: ProviderQueries::sort($source),
                settings: ProviderQueries::settings(),
            ))
            ->setPerPageLimit($perPage)
            ->collect()
            ->filter(fn (mixed $item): bool => is_array($item))
            ->values();
    }

    /**
     * The most recently uploaded in-use JPEG or PNG ProviderFile record.
     *
     * @return array<string, mixed>|null
     */
    public function latestImageFile(string $providerId): ?array
    {
        $items = $this->firstPage(new QueryRequest(
            source: QuerySource::ProviderFile,
            fields: ProviderQueries::fields(QuerySource::ProviderFile),
            filter: ProviderQueries::filter(QuerySource::ProviderFile, $providerId),
            sort: ProviderQueries::sort(QuerySource::ProviderFile),
            settings: ProviderQueries::settings(),
        ), 10);

        $file = $items[0] ?? null;

        return is_array($file) ? $file : null;
    }

    public function downloadImage(string $providerId, string $fileUid): ProviderImage
    {
        $response = $this->connector->send(new DownloadProviderFileRequest($providerId, $fileUid));
        $response->throw();

        return ProviderImage::fromDownload($providerId, $fileUid, $response->body());
    }

    /**
     * Find and download a provider's latest image. Sends two requests.
     */
    public function image(string $providerId): ?ProviderImage
    {
        $file = $this->latestImageFile($providerId);
        $fileUid = self::nullableString($file['Uid'] ?? null);

        return $fileUid === null ? null : $this->downloadImage($providerId, $fileUid);
    }

    /**
     * @param  array<string, mixed>  $filter
     * @param  array<int, string>|null  $fields
     * @return array<string, mixed>|null
     */
    private function firstDemographic(array $filter, ?array $fields): ?array
    {
        $items = $this->firstPage(new QueryRequest(
            source: QuerySource::Demographic,
            fields: $fields ?? ProviderQueries::fields(QuerySource::Demographic),
            filter: $filter,
            settings: ProviderQueries::settings(),
        ), 1);

        $record = $items[0] ?? null;

        return is_array($record) ? $record : null;
    }

    /**
     * @return array<int, mixed>
     */
    private function firstPage(QueryRequest $request, int $perPage): array
    {
        return iterator_to_array(
            $this->connector
                ->paginate($request)
                ->setPerPageLimit($perPage)
                ->setMaxPages(1)
                ->items(),
            preserve_keys: false,
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_scalar($value) && trim((string) $value) !== ''
            ? trim((string) $value)
            : null;
    }
}
