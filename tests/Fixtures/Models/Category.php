<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed\Tests\Fixtures\Models;

use Nsumbadze\WhereUsed\Contracts\HasReferences;
use Nsumbadze\WhereUsed\Reference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model implements HasReferences
{
    protected $guarded = [];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public static function references(): array
    {
        return [
            Reference::make(Banner::class)
                ->label('banners')
                ->where(fn (Builder $query, Model $record): Builder => $query->whereJsonContains('category_ids', $record->getKey())),
        ];
    }
}
