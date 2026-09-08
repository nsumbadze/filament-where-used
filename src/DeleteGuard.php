<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed;

use Nsumbadze\WhereUsed\Enums\DeleteBehaviour;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Wires the reference check into Filament's delete actions.
 *
 * Applied globally through DeleteAction::configureUsing() by the plugin. If a
 * resource defines its own ->before() hook it replaces ours, so call
 * DeleteGuard::apply($action) again inside that hook chain, or use
 * WhereUsed::isReferenced() directly.
 */
final class DeleteGuard
{
    public static function apply(DeleteAction $action): void
    {
        $action
            ->modalDescription(fn (Model $record): ?string => self::description($record))
            ->modalSubmitAction(function (Action $submitAction, Model $record): Action {
                if (self::behaviour() === DeleteBehaviour::Block && self::references()->isReferenced($record)) {
                    $submitAction->disabled();
                }

                return $submitAction;
            })
            ->before(function (DeleteAction $action, Model $record): void {
                self::guard($action, collect([$record]));
            });
    }

    public static function applyToBulk(DeleteBulkAction $action): void
    {
        $action
            ->modalDescription(fn (EloquentCollection $records): ?string => self::bulkDescription($records))
            ->before(function (DeleteBulkAction $action, EloquentCollection $records): void {
                self::guard($action, $records);
            });
    }

    /**
     * Cancel the action when blocking and any record is referenced.
     *
     * @param  Collection<int, Model>  $records
     */
    public static function guard(Action $action, Collection $records): void
    {
        if (self::behaviour() !== DeleteBehaviour::Block) {
            return;
        }

        $usages = self::usagesAcross($records);

        if ($usages->isEmpty()) {
            return;
        }

        Notification::make()
            ->danger()
            ->title(__('filament-where-used::where-used.blocked_title'))
            ->body(self::references()->summary($usages))
            ->send();

        $action->cancel();
    }

    public static function description(Model $record): ?string
    {
        $usages = self::references()->usagesOf($record);

        if ($usages->isEmpty()) {
            return null;
        }

        $summary = self::references()->summary($usages);

        return self::behaviour() === DeleteBehaviour::Block
            ? __('filament-where-used::where-used.blocked', ['summary' => $summary])
            : __('filament-where-used::where-used.confirm', ['summary' => $summary]);
    }

    /**
     * @param  EloquentCollection<int, Model>  $records
     */
    public static function bulkDescription(EloquentCollection $records): ?string
    {
        $usages = self::usagesAcross($records);

        if ($usages->isEmpty()) {
            return null;
        }

        $summary = self::references()->summary($usages);

        return self::behaviour() === DeleteBehaviour::Block
            ? __('filament-where-used::where-used.blocked', ['summary' => $summary])
            : __('filament-where-used::where-used.confirm', ['summary' => $summary]);
    }

    /**
     * Usage counts summed across many records, keyed by reference.
     *
     * @param  Collection<int, Model>  $records
     * @return Collection<int, ReferenceCount>
     */
    public static function usagesAcross(Collection $records): Collection
    {
        /** @var array<string, ReferenceCount> $totals */
        $totals = [];

        foreach ($records as $record) {
            foreach (self::references()->usagesOf($record) as $usage) {
                $key = $usage->reference->key();

                $totals[$key] = new ReferenceCount(
                    $usage->reference,
                    ($totals[$key]->count ?? 0) + $usage->count,
                );
            }
        }

        return collect(array_values($totals));
    }

    private static function behaviour(): DeleteBehaviour
    {
        return WhereUsedPlugin::get()->getDeleteBehaviour();
    }

    private static function references(): References
    {
        return app(References::class);
    }
}
