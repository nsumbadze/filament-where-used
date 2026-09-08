<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed\Tests\Fixtures\Resources\CategoryResource\Pages;

use Nsumbadze\WhereUsed\Tests\Fixtures\Resources\CategoryResource;
use Nsumbadze\WhereUsed\Widgets\ReferencesWidget;
use Filament\Resources\Pages\ViewRecord;

class ViewCategory extends ViewRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getFooterWidgets(): array
    {
        return [ReferencesWidget::class];
    }
}
