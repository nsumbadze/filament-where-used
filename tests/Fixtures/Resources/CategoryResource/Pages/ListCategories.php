<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed\Tests\Fixtures\Resources\CategoryResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Nsumbadze\WhereUsed\Tests\Fixtures\Resources\CategoryResource;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;
}
