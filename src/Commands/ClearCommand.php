<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed\Commands;

use Nsumbadze\WhereUsed\ReferenceMap;
use Illuminate\Console\Command;

class ClearCommand extends Command
{
    protected $signature = 'where-used:clear';

    protected $description = 'Forget the cached reference map';

    public function handle(ReferenceMap $map): int
    {
        $map->clear();

        $this->components->info('Reference map cleared.');

        return self::SUCCESS;
    }
}
