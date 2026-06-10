<?php

namespace Hwkdo\IntranetAppCisco\Commands;

use Illuminate\Console\Command;

class IntranetAppCiscoCommand extends Command
{
    public $signature = 'intranet-app-cisco';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
