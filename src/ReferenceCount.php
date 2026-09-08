<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed;

final class ReferenceCount
{
    public function __construct(
        public readonly Reference $reference,
        public readonly int $count,
    ) {}

    public function label(): string
    {
        return $this->reference->getLabel($this->count);
    }
}
