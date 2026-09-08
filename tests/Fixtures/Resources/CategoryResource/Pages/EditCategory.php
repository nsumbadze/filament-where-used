<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed\Tests\Fixtures\Resources\CategoryResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Nsumbadze\WhereUsed\Tests\Fixtures\Resources\CategoryResource;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
