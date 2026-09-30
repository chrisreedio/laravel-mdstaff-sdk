<?php

declare(strict_types=1);

namespace ChrisReedIO\MDStaff\Requests;

use ChrisReedIO\MDStaff\Support\MDStaffConfig;
use InvalidArgumentException;
use Saloon\Enums\Method;
use Saloon\Http\Request;

class DownloadProviderFileRequest extends Request
{
    protected Method $method = Method::POST;

    public function __construct(
        protected string $providerId,
        protected string $fileUid,
    ) {
        if ($this->providerId === '' || $this->fileUid === '') {
            throw new InvalidArgumentException('MDStaff provider and file identifiers are required.');
        }
    }

    public function resolveEndpoint(): string
    {
        return '/api/'.rawurlencode(MDStaffConfig::accountCode())
            .'/providers/'.rawurlencode($this->providerId)
            .'/providerfile/'.rawurlencode($this->fileUid)
            .'/download';
    }
}
