<?php

namespace ChrisReedIO\MDStaff\Commands;

use Illuminate\Console\Command;

class MDStaffCommand extends Command
{
    public $signature = 'laravel-mdstaff-sdk';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
