<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed\Facades;

use Illuminate\Support\Facades\Facade;
use Nsumbadze\WhereUsed\References;

/**
 * @method static \Illuminate\Support\Collection<int, \Nsumbadze\WhereUsed\ReferenceCount> countsFor(\Illuminate\Database\Eloquent\Model $record)
 * @method static \Illuminate\Support\Collection<int, \Nsumbadze\WhereUsed\ReferenceCount> usagesOf(\Illuminate\Database\Eloquent\Model $record)
 * @method static bool isReferenced(\Illuminate\Database\Eloquent\Model $record)
 * @method static string summary(\Illuminate\Support\Collection<int, \Nsumbadze\WhereUsed\ReferenceCount> $usages)
 *
 * @see References
 */
class WhereUsed extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return References::class;
    }
}
