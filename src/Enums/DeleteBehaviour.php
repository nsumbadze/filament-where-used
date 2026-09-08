<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed\Enums;

enum DeleteBehaviour: string
{
    case Block = 'block';
    case Confirm = 'confirm';
}
