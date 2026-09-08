<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Nsumbadze\WhereUsed\DeleteGuard;
use Nsumbadze\WhereUsed\Tests\Fixtures\Models\Category;
use Nsumbadze\WhereUsed\Tests\Fixtures\Models\Product;
use Nsumbadze\WhereUsed\Tests\Fixtures\Resources\CategoryResource\Pages\EditCategory;
use Nsumbadze\WhereUsed\Tests\Fixtures\Resources\CategoryResource\Pages\ListCategories;

use function Pest\Livewire\livewire;

function usedCategory(): Category
{
    $category = Category::query()->create(['name' => 'Used']);
    Product::query()->create(['name' => 'Hat', 'category_id' => $category->id]);

    return $category;
}

it('blocks deleting a referenced record from the table', function (): void {
    $category = usedCategory();

    livewire(ListCategories::class)
        ->callAction(TestAction::make('delete')->table($category))
        ->assertNotified('Cannot delete');

    expect(Category::query()->find($category->id))->not->toBeNull();
});

it('blocks deleting a referenced record from the edit page header', function (): void {
    $category = usedCategory();

    livewire(EditCategory::class, ['record' => $category->getRouteKey()])
        ->callAction('delete')
        ->assertNotified('Cannot delete');

    expect(Category::query()->find($category->id))->not->toBeNull();
});

it('still deletes an unreferenced record', function (): void {
    $category = Category::query()->create(['name' => 'Free']);

    livewire(ListCategories::class)
        ->callAction(TestAction::make('delete')->table($category))
        ->assertNotNotified('Cannot delete');

    expect(Category::query()->find($category->id))->toBeNull();
});

it('blocks bulk deletion when any selected record is referenced', function (): void {
    $used = usedCategory();
    $free = Category::query()->create(['name' => 'Free']);

    livewire(ListCategories::class)
        ->selectTableRecords([$used->getKey(), $free->getKey()])
        ->callAction(TestAction::make('delete')->table()->bulk())
        ->assertNotified('Cannot delete');

    expect(Category::query()->count())->toBe(2);
});

it('lets the user delete anyway in confirm mode', function (): void {
    config()->set('filament-where-used.on_delete', 'confirm');

    $category = usedCategory();

    livewire(ListCategories::class)
        ->callAction(TestAction::make('delete')->table($category))
        ->assertNotNotified('Cannot delete');

    expect(Category::query()->find($category->id))->toBeNull();
});

it('describes the references in the modal', function (): void {
    $category = usedCategory();

    expect(DeleteGuard::description($category))
        ->toBe('Used by 1 product. Deleting is blocked until those references are removed or reassigned.');

    config()->set('filament-where-used.on_delete', 'confirm');

    expect(DeleteGuard::description($category))
        ->toBe('Used by 1 product. Deleting will leave those records without this reference.');

    expect(DeleteGuard::description(Category::query()->create(['name' => 'Free'])))->toBeNull();
});
