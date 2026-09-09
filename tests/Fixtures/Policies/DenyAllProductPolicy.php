<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed\Tests\Fixtures\Policies;

use Nsumbadze\WhereUsed\Tests\Fixtures\Models\Product;
use Nsumbadze\WhereUsed\Tests\Fixtures\Models\User;

class DenyAllProductPolicy
{
    public function view(User $user, Product $product): bool
    {
        return false;
    }

    public function update(User $user, Product $product): bool
    {
        return false;
    }
}
