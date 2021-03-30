<?php

declare(strict_types=1);

namespace Orion\Commands;

use Illuminate\Console\Command;

class BuildSpecs extends Command
{
    protected $signature = 'orion:specs {--format=yaml}';

    protected $description = 'Generates API specifications in the given format';

    public function handle()
    {
        //
    }
}
