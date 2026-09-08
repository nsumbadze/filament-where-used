<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed\Tests\Fixtures\Resources\ProductResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Nsumbadze\WhereUsed\Tests\Fixtures\Resources\ProductResource;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;
}
