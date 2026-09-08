<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed\Tests\Fixtures\Resources\ProductResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Nsumbadze\WhereUsed\Tests\Fixtures\Resources\ProductResource;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;
}
