<?php

declare(strict_types=1);

namespace ChrisReedIO\MDStaff\Pagination;

use ChrisReedIO\MDStaff\MDStaffConnector;
use ChrisReedIO\MDStaff\Requests\QueryRequest;
use InvalidArgumentException;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Paginator;
use UnexpectedValueException;

class QueryPaginator extends Paginator
{
    public const int DEFAULT_PER_PAGE_LIMIT = 100;

    public const int MAX_PER_PAGE_LIMIT = 2000;

    public function __construct(MDStaffConnector $connector, QueryRequest $request)
    {
        parent::__construct($connector, $request);

        $this->setPerPageLimit(self::DEFAULT_PER_PAGE_LIMIT);
    }

    public function setPerPageLimit(?int $perPageLimit): static
    {
        $perPageLimit ??= self::DEFAULT_PER_PAGE_LIMIT;

        if ($perPageLimit < 1 || $perPageLimit > self::MAX_PER_PAGE_LIMIT) {
            throw new InvalidArgumentException('MDStaff results per page must be between 1 and 2000.');
        }

        return parent::setPerPageLimit($perPageLimit);
    }

    public function setStartPage(int $startPage): static
    {
        if ($startPage < 1) {
            throw new InvalidArgumentException('MDStaff page must be at least 1.');
        }

        return parent::setStartPage($startPage);
    }

    protected function applyPagination(Request $request): Request
    {
        if (! $request instanceof QueryRequest) {
            throw new InvalidArgumentException('Only MDStaff query requests can be paginated.');
        }

        $request->body()->add('page', $this->getCurrentPage() + 1);
        $request->body()->add('resultsperpage', $this->perPageLimit);

        return $request;
    }

    protected function isLastPage(Response $response): bool
    {
        return count($this->responseItems($response)) < $this->perPageLimit;
    }

    /**
     * @return array<int, mixed>
     */
    protected function getPageItems(Response $response, Request $request): array
    {
        return $this->responseItems($response);
    }

    /**
     * @return array<int, mixed>
     */
    private function responseItems(Response $response): array
    {
        $items = $response->json();

        if (! is_array($items) || ! array_is_list($items)) {
            throw new UnexpectedValueException('MDStaff query responses must contain a JSON array.');
        }

        return $items;
    }
}
