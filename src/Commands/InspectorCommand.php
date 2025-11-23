<?php

namespace Syndicate\Inspector\Commands;

use Illuminate\Console\Command;

class InspectorCommand extends Command
{
    public $signature = 'inspector';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
