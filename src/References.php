<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed;

use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Counts and lists the records that reference a given record.
 */
class References
{
    public function __construct(protected ReferenceMap $map) {}

    /**
     * One entry per reference, including zero counts.
     *
     * @return Collection<int, ReferenceCount>
     */
    public function countsFor(Model $record): Collection
    {
        return collect($this->map->for($record::class))
            ->map(fn (Reference $reference): ReferenceCount => new ReferenceCount(
                $reference,
                $this->query($reference, $record)->count(),
            ))
            ->values();
    }

    /**
     * Only references with at least one referencing record.
     *
     * @return Collection<int, ReferenceCount>
     */
    public function usagesOf(Model $record): Collection
    {
        return $this->countsFor($record)
            ->filter(fn (ReferenceCount $count): bool => $count->count > 0)
            ->values();
    }

    public function isReferenced(Model $record): bool
    {
        foreach ($this->map->for($record::class) as $reference) {
            if ($this->query($reference, $record)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Referencing records for one reference, limited.
     *
     * @return Collection<int, Model>
     */
    public function records(Reference $reference, Model $record, int $limit): Collection
    {
        return $this->query($reference, $record)->limit($limit)->get();
    }

    /**
     * Query on the source model, scoped through its Filament resource when
     * one exists so tenancy and soft-delete behaviour match the panel.
     *
     * @return Builder<Model>
     */
    public function query(Reference $reference, Model $record): Builder
    {
        /** @var class-string<\Filament\Resources\Resource>|null $resource */
        $resource = Filament::getModelResource($reference->source);

        $query = $resource !== null
            ? $resource::getEloquentQuery()
            : $reference->source::query();

        return $reference->apply($query, $record);
    }

    /**
     * "Used by 14 products and 3 promotions."
     *
     * @param  Collection<int, ReferenceCount>  $usages
     */
    public function summary(Collection $usages): string
    {
        $parts = $usages
            ->map(fn (ReferenceCount $usage): string => trans_choice('filament-where-used::where-used.count', $usage->count, [
                'count' => $usage->count,
                'label' => $usage->label(),
            ]))
            ->all();

        if ($parts === []) {
            return __('filament-where-used::where-used.none');
        }

        $last = array_pop($parts);

        $list = $parts === []
            ? $last
            : implode(', ', $parts) . ' ' . __('filament-where-used::where-used.and') . ' ' . $last;

        return __('filament-where-used::where-used.used_by', ['list' => $list]);
    }
}
