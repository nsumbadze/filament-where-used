<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Nsumbadze\WhereUsed\Enums\DeleteBehaviour;

/**
 * Wires the reference check into Filament's delete actions.
 *
 * Applied globally through DeleteAction::configureUsing() by the plugin, so it
 * runs before any per-resource configuration. Filament keeps a single
 * ->before() hook per action: a resource that sets its own replaces the
 * server-side guard and should call DeleteGuard::guard() (or
 * WhereUsed::isReferenced()) inside it. The disabled submit button and the
 * modal description do not depend on that hook.
 */
final class DeleteGuard
{
    public static function apply(DeleteAction $action): void
    {
        $action
            ->modalDescription(fn (Model $record): ?string => self::describe(self::references()->usagesOf($record)))
            ->modalSubmitAction(function (Action $submitAction, Model $record): Action {
                if (self::isBlocking() && self::references()->isReferenced($record)) {
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
            ->modalDescription(fn (EloquentCollection $records): ?string => self::describe(self::references()->usagesAcross($records)))
            ->modalSubmitAction(function (Action $submitAction, EloquentCollection $records): Action {
                if (self::isBlocking() && self::references()->usagesAcross($records)->isNotEmpty()) {
                    $submitAction->disabled();
                }

                return $submitAction;
            })
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
        if (! self::isBlocking()) {
            return;
        }

        $usages = self::references()->usagesAcross($records);

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
        return self::describe(self::references()->usagesOf($record));
    }

    /**
     * @param  EloquentCollection<int, Model>  $records
     */
    public static function bulkDescription(EloquentCollection $records): ?string
    {
        return self::describe(self::references()->usagesAcross($records));
    }

    /**
     * @param  Collection<int, ReferenceCount>  $usages
     */
    private static function describe(Collection $usages): ?string
    {
        if ($usages->isEmpty()) {
            return null;
        }

        $summary = self::references()->summary($usages);

        return self::isBlocking()
            ? __('filament-where-used::where-used.blocked', ['summary' => $summary])
            : __('filament-where-used::where-used.confirm', ['summary' => $summary]);
    }

    private static function isBlocking(): bool
    {
        return WhereUsedPlugin::currentBehaviour() === DeleteBehaviour::Block;
    }

    private static function references(): References
    {
        return app(References::class);
    }
}
