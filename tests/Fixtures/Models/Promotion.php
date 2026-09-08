<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Promotion extends Model
{
    protected $guarded = [];

    public function promotable(): MorphTo
    {
        return $this->morphTo();
    }
}
