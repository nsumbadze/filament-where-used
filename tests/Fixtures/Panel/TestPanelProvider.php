<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed\Tests\Fixtures\Panel;

use Nsumbadze\WhereUsed\Tests\Fixtures\Resources\CategoryResource;
use Nsumbadze\WhereUsed\Tests\Fixtures\Resources\ProductResource;
use Nsumbadze\WhereUsed\WhereUsedPlugin;
use Filament\Panel;
use Filament\PanelProvider;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->default()
            ->login()
            ->resources([
                CategoryResource::class,
                ProductResource::class,
            ])
            ->plugin(
                WhereUsedPlugin::make()
                    ->modelPaths([__DIR__ . '/../Models'])
                    ->onDelete(fn () => config('filament-where-used.on_delete')),
            );
    }
}
