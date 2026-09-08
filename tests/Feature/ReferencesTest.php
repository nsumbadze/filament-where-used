<?php

declare(strict_types=1);

use Nsumbadze\WhereUsed\Facades\WhereUsed;
use Nsumbadze\WhereUsed\ReferenceCount;
use Nsumbadze\WhereUsed\Tests\Fixtures\Models\Banner;
use Nsumbadze\WhereUsed\Tests\Fixtures\Models\Category;
use Nsumbadze\WhereUsed\Tests\Fixtures\Models\Product;
use Nsumbadze\WhereUsed\Tests\Fixtures\Models\Promotion;

function seedCategoryWithUsages(): Category
{
    $category = Category::query()->create(['name' => 'Summer']);
    $other = Category::query()->create(['name' => 'Winter']);

    Product::query()->create(['name' => 'Hat', 'category_id' => $category->id]);
    Product::query()->create(['name' => 'Shirt', 'category_id' => $category->id]);
    Product::query()->create(['name' => 'Coat', 'category_id' => $other->id]);

    Promotion::query()->create(['name' => 'Sale', 'promotable_type' => $category->getMorphClass(), 'promotable_id' => $category->id]);

    Banner::query()->create(['name' => 'Hero', 'category_ids' => [$category->id, 99]]);
    Banner::query()->create(['name' => 'Side', 'category_ids' => [$other->id]]);

    return $category;
}

it('counts belongsTo, morphTo and custom references for a record', function (): void {
    $category = seedCategoryWithUsages();

    $usages = WhereUsed::usagesOf($category);

    expect($usages)->toHaveCount(3)
        ->and($usages->map(fn (ReferenceCount $u): array => [$u->reference->source, $u->count])->all())->toBe([
            [Product::class, 2],
            [Promotion::class, 1],
            [Banner::class, 1],
        ]);
});

it('reports zero usages for an unreferenced record', function (): void {
    $category = Category::query()->create(['name' => 'Empty']);

    expect(WhereUsed::isReferenced($category))->toBeFalse()
        ->and(WhereUsed::usagesOf($category))->toBeEmpty()
        ->and(WhereUsed::countsFor($category))->toHaveCount(3);
});

it('builds a readable summary using resource labels when available', function (): void {
    $category = seedCategoryWithUsages();

    expect(WhereUsed::summary(WhereUsed::usagesOf($category)))
        ->toBe('Used by 2 products, 1 promotion and 1 banners.');
});
