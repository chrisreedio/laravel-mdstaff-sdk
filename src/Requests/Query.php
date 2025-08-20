<?php

namespace ChrisReedIO\MDStaff\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class Query extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected string $source,
        protected array $fields = ['*'],
        protected array $filters = [],
        protected array $sort = [],
        protected array $settings = [],
        protected int $resultsPerPage = 100,
        protected int $page = 1,
    ) {}

    public function resolveEndpoint(): string
    {
        $accountCode = config('mdstaff-sdk.account_code');

        return "/api/{$accountCode}/query";
    }

    public function defaultBody(): array
    {
        return [
            'source' => $this->source,
            'fields' => $this->fields,
            'filters' => $this->filters,
            'sort' => $this->sort,
            'settings' => $this->settings,
            'resultsPerPage' => $this->resultsPerPage,
            'page' => $this->page,
        ];
    }
}
