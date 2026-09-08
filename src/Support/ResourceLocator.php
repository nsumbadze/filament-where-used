<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed\Support;

use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Model → resource lookup that never throws: outside a panel (console, jobs,
 * a panel without a default) Filament raises instead of returning null.
 */
final class ResourceLocator
{
    /** @var array<string, class-string<\Filament\Resources\Resource>|null> */
    private static array $memo = [];

    /**
     * @param  class-string<Model>|Model  $model
     * @return class-string<\Filament\Resources\Resource>|null
     */
    public static function for(string|Model $model): ?string
    {
        $class = $model instanceof Model ? $model::class : $model;

        if (array_key_exists($class, self::$memo)) {
            return self::$memo[$class];
        }

        try {
            /** @var class-string<\Filament\Resources\Resource>|null $resource */
            $resource = Filament::getModelResource($class);
        } catch (Throwable) {
            $resource = null;
        }

        return self::$memo[$class] = $resource;
    }

    public static function flush(): void
    {
        self::$memo = [];
    }
}
