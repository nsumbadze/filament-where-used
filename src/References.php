<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Nsumbadze\WhereUsed\Support\ResourceLocator;

/**
 * Counts and lists the records that reference a given record.
 *
 * Counts are memoised per record for the lifetime of the instance (one
 * request), because a delete modal asks the same question three times:
 * description, submit button state, and the before-hook guard.
 */
class References
{
    /** @var array<string, Collection<int, ReferenceCount>> */
    protected array $memo = [];

    public function __construct(protected ReferenceMap $map) {}

    /**
     * One entry per reference, including zero counts.
     *
     * @return Collection<int, ReferenceCount>
     */
    public function countsFor(Model $record): Collection
    {
        $key = $this->memoKey($record);

        if (isset($this->memo[$key])) {
            return $this->memo[$key];
        }

        return $this->memo[$key] = collect($this->map->for($record::class))
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
        if (isset($this->memo[$this->memoKey($record)])) {
            return $this->usagesOf($record)->isNotEmpty();
        }

        foreach ($this->map->for($record::class) as $reference) {
            if ($this->query($reference, $record)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Usage counts summed across many records, one query per reference where
     * the reference supports batching (belongsTo, morphTo).
     *
     * @param  Collection<int, Model>  $records
     * @return Collection<int, ReferenceCount>
     */
    public function usagesAcross(Collection $records): Collection
    {
        $records = $records->values();

        if ($records->isEmpty()) {
            return collect();
        }

        if ($records->count() === 1) {
            /** @var Model $only */
            $only = $records->first();

            return $this->usagesOf($only);
        }

        /** @var array<string, ReferenceCount> $totals */
        $totals = [];

        foreach ($records->groupBy(fn (Model $record): string => $record::class) as $group) {
            /** @var Model $first */
            $first = $group->first();

            foreach ($this->map->for($first::class) as $reference) {
                $batched = $reference->applyMany($this->baseQuery($reference), $group);

                $count = $batched !== null
                    ? $batched->count()
                    : (int) $group->sum(fn (Model $record): int => $this->query($reference, $record)->count());

                if ($count === 0) {
                    continue;
                }

                $key = $reference->key();

                $totals[$key] = new ReferenceCount($reference, ($totals[$key]->count ?? 0) + $count);
            }
        }

        return collect(array_values($totals));
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
        return $reference->apply($this->baseQuery($reference), $record);
    }

    /**
     * Forget memoised counts (after a mutation within the same request).
     */
    public function flush(): void
    {
        $this->memo = [];
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

    /**
     * @return Builder<Model>
     */
    protected function baseQuery(Reference $reference): Builder
    {
        $resource = ResourceLocator::for($reference->source);

        return $resource !== null
            ? $resource::getEloquentQuery()
            : $reference->source::query();
    }

    protected function memoKey(Model $record): string
    {
        return $record::class . '#' . $record->getKey();
    }
}
