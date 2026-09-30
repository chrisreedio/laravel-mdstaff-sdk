<?php

declare(strict_types=1);

namespace ChrisReedIO\MDStaff\Commands;

use ChrisReedIO\MDStaff\Enums\QuerySource;
use ChrisReedIO\MDStaff\MDStaffConnector;
use ChrisReedIO\MDStaff\Requests\QueryRequest;
use Illuminate\Console\Command;
use Throwable;

class TestConnectionCommand extends Command
{
    public $signature = 'mdstaff:test';

    public $description = 'Run a read-only smoke test against the configured MDStaff integration';

    public function handle(): int
    {
        $startedAt = hrtime(true);

        try {
            $records = MDStaffConnector::forConfiguredFacility()
                ->paginate(
                    (new QueryRequest(
                        source: QuerySource::Demographic,
                        fields: ['ProviderID', 'NPI'],
                    ))->disableCaching(),
                )
                ->setPerPageLimit(1)
                ->setMaxPages(1)
                ->collect()
                ->values()
                ->all();
        } catch (Throwable $exception) {
            $this->components->error('MDStaff integration test failed.');
            $this->line($exception->getMessage());

            return self::FAILURE;
        }

        $firstRecord = $records[0] ?? null;
        $responseFields = is_array($firstRecord)
            ? implode(', ', array_keys($firstRecord))
            : 'none (empty response)';

        $this->components->info('MDStaff integration test passed.');
        $this->table(
            ['Check', 'Result'],
            [
                ['Source', QuerySource::Demographic->value],
                ['Requested fields', 'ProviderID, NPI'],
                ['Records returned', (string) count($records)],
                ['Response fields', $responseFields],
                ['Elapsed', number_format((hrtime(true) - $startedAt) / 1_000_000, 2).' ms'],
            ],
        );

        return self::SUCCESS;
    }
}
