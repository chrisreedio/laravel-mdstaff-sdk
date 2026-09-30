<?php

declare(strict_types=1);

namespace ChrisReedIO\MDStaff\Resources;

use ChrisReedIO\MDStaff\Enums\LookUpType;
use ChrisReedIO\MDStaff\Enums\QuerySource;
use ChrisReedIO\MDStaff\Pagination\QueryPaginator;
use ChrisReedIO\MDStaff\Requests\QueryRequest;
use Illuminate\Support\LazyCollection;

class LookUps extends Resource
{
    /**
     * Lazily page through the LookUp records of one type.
     *
     * @param  array<int, string>  $fields
     * @return LazyCollection<int, array<mixed>>
     */
    public function list(
        LookUpType|string $type,
        array $fields = ['LookUpID', 'Code', 'Description', 'LookUpType', 'Archived'],
    ): LazyCollection {
        return $this->connector
            ->paginate(new QueryRequest(
                source: QuerySource::LookUp,
                fields: $fields,
                filter: ['LookUpType' => $type instanceof LookUpType ? $type->value : $type],
            ))
            ->setPerPageLimit(QueryPaginator::MAX_PER_PAGE_LIMIT)
            ->collect()
            ->filter(fn (mixed $item): bool => is_array($item))
            ->values();
    }
}
