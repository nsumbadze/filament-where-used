<?php

declare(strict_types=1);

use Nsumbadze\WhereUsed\Tests\Fixtures\Models\Category;
use Nsumbadze\WhereUsed\Tests\Fixtures\Models\Product;
use Nsumbadze\WhereUsed\Tests\Fixtures\Resources\CategoryResource\Pages\ViewCategory;
use Nsumbadze\WhereUsed\Tests\Fixtures\Resources\ProductResource;
use Nsumbadze\WhereUsed\Widgets\ReferencesWidget;

use function Pest\Livewire\livewire;

it('lists referencing records with links to their resource pages', function (): void {
    $category = Category::query()->create(['name' => 'Used']);
    $product = Product::query()->create(['name' => 'Hat', 'category_id' => $category->id]);

    livewire(ReferencesWidget::class, ['record' => $category])
        ->assertSee('products')
        ->assertSee('Hat')
        ->assertSeeHtml(ProductResource::getUrl('edit', ['record' => $product]));
});

it('shows an empty state when nothing references the record', function (): void {
    $category = Category::query()->create(['name' => 'Free']);

    livewire(ReferencesWidget::class, ['record' => $category])
        ->assertSee('Nothing references this record.');
});

it('caps the listed records and reports the remainder', function (): void {
    config()->set('filament-where-used.widget_limit', 2);

    $category = Category::query()->create(['name' => 'Busy']);

    foreach (range(1, 5) as $i) {
        Product::query()->create(['name' => "Product {$i}", 'category_id' => $category->id]);
    }

    livewire(ReferencesWidget::class, ['record' => $category])
        ->assertSee('Product 1')
        ->assertSee('Product 2')
        ->assertDontSee('Product 3')
        ->assertSee('and 3 more');
});

it('renders as a footer widget on the view page', function (): void {
    $category = Category::query()->create(['name' => 'Used']);
    Product::query()->create(['name' => 'Hat', 'category_id' => $category->id]);

    livewire(ViewCategory::class, ['record' => $category->getRouteKey()])
        ->assertSuccessful()
        ->assertSeeLivewire(ReferencesWidget::class);
});
