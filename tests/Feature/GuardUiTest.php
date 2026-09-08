<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\DB;
use Nsumbadze\WhereUsed\Facades\WhereUsed;
use Nsumbadze\WhereUsed\References;
use Nsumbadze\WhereUsed\Tests\Fixtures\Models\Banner;
use Nsumbadze\WhereUsed\Tests\Fixtures\Models\Category;
use Nsumbadze\WhereUsed\Tests\Fixtures\Models\Product;
use Nsumbadze\WhereUsed\Tests\Fixtures\Resources\CategoryResource\Pages\ListCategories;

use function Pest\Livewire\livewire;

it('disables the confirm button of the delete modal for a referenced record', function (): void {
    $category = Category::query()->create(['name' => 'Used']);
    Product::query()->create(['name' => 'Hat', 'category_id' => $category->id]);

    $component = livewire(ListCategories::class)
        ->mountAction(TestAction::make('delete')->table($category));

    $action = $component->instance()->getMountedAction();

    expect($action)->not->toBeNull()
        ->and($action->getModalSubmitAction()?->isDisabled())->toBeTrue()
        ->and((string) $action->getModalDescription())->toContain('Used by 1 product');
});

it('keeps the confirm button enabled in confirm mode', function (): void {
    config()->set('filament-where-used.on_delete', 'confirm');

    $category = Category::query()->create(['name' => 'Used']);
    Product::query()->create(['name' => 'Hat', 'category_id' => $category->id]);

    $component = livewire(ListCategories::class)
        ->mountAction(TestAction::make('delete')->table($category));

    $action = $component->instance()->getMountedAction();

    expect($action?->getModalSubmitAction()?->isDisabled())->toBeFalse()
        ->and((string) $action?->getModalDescription())->toContain('Deleting will leave those records');
});

it('memoises counts per record within a request', function (): void {
    $category = Category::query()->create(['name' => 'Used']);
    Product::query()->create(['name' => 'Hat', 'category_id' => $category->id]);

    DB::enableQueryLog();

    WhereUsed::usagesOf($category);
    $queriesAfterFirst = count(DB::getQueryLog());

    WhereUsed::usagesOf($category);
    WhereUsed::isReferenced($category);

    expect($queriesAfterFirst)->toBe(3)
        ->and(count(DB::getQueryLog()))->toBe(3);

    app(References::class)->flush();
    WhereUsed::usagesOf($category);

    expect(count(DB::getQueryLog()))->toBe(6);
});

it('batches bulk usage counts into one query per reference and merges custom references', function (): void {
    $a = Category::query()->create(['name' => 'A']);
    $b = Category::query()->create(['name' => 'B']);
    Product::query()->create(['name' => 'P1', 'category_id' => $a->id]);
    Product::query()->create(['name' => 'P2', 'category_id' => $b->id]);
    Banner::query()->create(['name' => 'Hero', 'category_ids' => [$a->id]]);
    Banner::query()->create(['name' => 'Side', 'category_ids' => [$b->id]]);

    DB::enableQueryLog();

    $usages = WhereUsed::usagesAcross(collect([$a, $b]));

    // products + promotions batched (2 queries), banners custom (1 per record).
    expect(count(DB::getQueryLog()))->toBe(4)
        ->and($usages->map(fn ($u): array => [class_basename($u->reference->source), $u->count])->all())
        ->toBe([['Product', 2], ['Banner', 2]]);
});
