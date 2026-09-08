<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed\Tests\Fixtures\Resources\CategoryResource\Pages;

use Nsumbadze\WhereUsed\Tests\Fixtures\Resources\CategoryResource;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;
}
