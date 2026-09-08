<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed\Support;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy-aware URL to open a record: View when allowed, else Edit, else none.
 */
final class RecordLink
{
    public static function for(Model $record): ?string
    {
        /** @var class-string<\Filament\Resources\Resource>|null $resource */
        $resource = Filament::getModelResource($record);

        if ($resource === null) {
            return null;
        }

        $pages = $resource::getPages();

        if (isset($pages['view']) && $resource::canView($record)) {
            return $resource::getUrl('view', ['record' => $record]);
        }

        if (isset($pages['edit']) && $resource::canEdit($record)) {
            return $resource::getUrl('edit', ['record' => $record]);
        }

        return null;
    }

    public static function title(Model $record): string
    {
        /** @var class-string<\Filament\Resources\Resource>|null $resource */
        $resource = Filament::getModelResource($record);

        if ($resource !== null) {
            return (string) $resource::getRecordTitle($record);
        }

        return (string) $record->getKey();
    }
}
