<?php

declare(strict_types=1);

namespace ChrisReedIO\MDStaff\Resources;

use ChrisReedIO\MDStaff\Requests\GetFacilitiesRequest;
use UnexpectedValueException;

class Facilities extends Resource
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        $response = $this->connector->send(new GetFacilitiesRequest);
        $response->throw();

        $facilities = $response->json();

        if (! is_array($facilities) || ! array_is_list($facilities)) {
            throw new UnexpectedValueException('MDStaff facility responses must contain a JSON array.');
        }

        return array_values(array_filter($facilities, is_array(...)));
    }
}
