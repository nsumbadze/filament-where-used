<?php

declare(strict_types=1);

use Nsumbadze\WhereUsed\Reference;
use Nsumbadze\WhereUsed\ReferenceMap;
use Nsumbadze\WhereUsed\Support\ModelDiscovery;
use Nsumbadze\WhereUsed\Tests\Fixtures\Models\Banner;
use Nsumbadze\WhereUsed\Tests\Fixtures\Models\Category;
use Nsumbadze\WhereUsed\Tests\Fixtures\Models\Product;
use Nsumbadze\WhereUsed\Tests\Fixtures\Models\Promotion;
use Nsumbadze\WhereUsed\Tests\Fixtures\Models\Tag;
use Nsumbadze\WhereUsed\Tests\Fixtures\Models\User;
use Illuminate\Support\Facades\Cache;

it('discovers concrete models in the configured paths', function (): void {
    $classes = ModelDiscovery::classesIn([__DIR__ . '/../Fixtures/Models']);

    expect($classes)->toBe([Banner::class, Category::class, Product::class, Promotion::class, Tag::class, User::class]);
});

it('builds an inverse map from typed belongsTo and morphTo relations', function (): void {
    $map = app(ReferenceMap::class)->usePaths([__DIR__ . '/../Fixtures/Models'])->rebuild();

    expect($map)->toHaveKeys([Category::class, ReferenceMap::MORPH_KEY])
        ->and($map[Category::class])->toHaveCount(1)
        ->and($map[Category::class][0])->toMatchArray([
            'source' => Product::class,
            'type' => Reference::TYPE_BELONGS_TO,
            'relation' => 'category',
            'foreignKey' => 'category_id',
            'ownerKey' => 'id',
        ])
        ->and($map[ReferenceMap::MORPH_KEY][0])->toMatchArray([
            'source' => Promotion::class,
            'type' => Reference::TYPE_MORPH_TO,
            'foreignKey' => 'promotable_id',
            'morphType' => 'promotable_type',
        ]);
});

it('merges custom references from HasReferences and honours ignore', function (): void {
    $map = app(ReferenceMap::class)->usePaths([__DIR__ . '/../Fixtures/Models']);

    $sources = array_map(fn (Reference $r): string => $r->source, $map->for(Category::class));

    expect($sources)->toBe([Product::class, Promotion::class, Banner::class]);

    $map->ignore([Promotion::class]);

    $sources = array_map(fn (Reference $r): string => $r->source, $map->for(Category::class));

    expect($sources)->toBe([Product::class, Banner::class]);
});

it('caches the map and clears it on demand', function (): void {
    $map = app(ReferenceMap::class)->usePaths([__DIR__ . '/../Fixtures/Models']);

    $map->rebuild();

    expect(Cache::has('filament-where-used.map'))->toBeTrue();

    $map->clear();

    expect(Cache::has('filament-where-used.map'))->toBeFalse();
});

it('exposes cache and clear commands', function (): void {
    $this->artisan('where-used:cache')->assertSuccessful();

    expect(Cache::has('filament-where-used.map'))->toBeTrue();

    $this->artisan('where-used:clear')->assertSuccessful();

    expect(Cache::has('filament-where-used.map'))->toBeFalse();
});
