<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed\Commands;

use Illuminate\Console\Command;
use Nsumbadze\WhereUsed\ReferenceMap;

class CacheCommand extends Command
{
    protected $signature = 'where-used:cache';

    protected $description = 'Discover model references and cache the reference map';

    public function handle(ReferenceMap $map): int
    {
        $map->usePaths(config('filament-where-used.model_paths', []));

        $result = $map->rebuild();

        $references = array_sum(array_map('count', $result));

        $this->components->info("Cached {$references} references across " . count($result) . ' target models.');

        foreach ($result as $target => $sources) {
            $this->components->twoColumnDetail($target, (string) count($sources));
        }

        return self::SUCCESS;
    }
}
