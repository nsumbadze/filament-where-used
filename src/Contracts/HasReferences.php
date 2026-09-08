<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed\Contracts;

use Nsumbadze\WhereUsed\Reference;

/**
 * Implement on a model that is referenced in ways the scanner cannot see
 * (JSON columns, pivot-less conventions, external keys).
 */
interface HasReferences
{
    /**
     * @return array<int, Reference>
     */
    public static function references(): array;
}
