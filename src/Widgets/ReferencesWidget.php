<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed\Widgets;

use Nsumbadze\WhereUsed\ReferenceCount;
use Nsumbadze\WhereUsed\References;
use Nsumbadze\WhereUsed\Support\RecordLink;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

/**
 * Footer widget for View / Edit pages: lists the records referencing $record.
 * Resource pages pass `record` automatically through getWidgetData().
 */
class ReferencesWidget extends Widget
{
    /**
     * @var view-string
     */
    protected string $view = 'filament-where-used::widgets.references';

    protected int|string|array $columnSpan = 'full';

    public ?Model $record = null;

    /**
     * @return array<int, array{label: string, count: int, records: array<int, array{title: string, url: ?string}>}>
     */
    public function getGroups(): array
    {
        if ($this->record === null) {
            return [];
        }

        $references = app(References::class);
        $limit = (int) config('filament-where-used.widget_limit', 5);

        return $references
            ->usagesOf($this->record)
            ->map(fn (ReferenceCount $usage): array => [
                'label' => $usage->label(),
                'count' => $usage->count,
                'records' => $references
                    ->records($usage->reference, $this->record, $limit)
                    ->map(fn (Model $record): array => [
                        'title' => RecordLink::title($record),
                        'url' => RecordLink::for($record),
                    ])
                    ->all(),
            ])
            ->all();
    }
}
