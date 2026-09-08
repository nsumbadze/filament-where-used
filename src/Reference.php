<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Nsumbadze\WhereUsed\Support\ResourceLocator;

/**
 * One way a source model can point at a target record.
 *
 * Discovered references are plain data (cacheable). Custom references carry a
 * closure and are resolved at runtime from HasReferences::references().
 */
final class Reference
{
    public const TYPE_BELONGS_TO = 'belongsTo';

    public const TYPE_MORPH_TO = 'morphTo';

    public const TYPE_CUSTOM = 'custom';

    private ?string $label = null;

    private ?Closure $constraint = null;

    private ?string $key = null;

    /**
     * @param  class-string<Model>  $source
     */
    private function __construct(
        public readonly string $source,
        public readonly string $type,
        public readonly ?string $relation = null,
        public readonly ?string $foreignKey = null,
        public readonly ?string $ownerKey = null,
        public readonly ?string $morphType = null,
    ) {}

    /**
     * @param  class-string<Model>  $source
     */
    public static function make(string $source): self
    {
        return new self($source, self::TYPE_CUSTOM);
    }

    /**
     * @param  class-string<Model>  $source
     */
    public static function belongsTo(string $source, string $relation, string $foreignKey, string $ownerKey): self
    {
        return new self($source, self::TYPE_BELONGS_TO, $relation, $foreignKey, $ownerKey);
    }

    /**
     * @param  class-string<Model>  $source
     */
    public static function morphTo(string $source, string $relation, string $foreignKey, string $morphType): self
    {
        return new self($source, self::TYPE_MORPH_TO, $relation, $foreignKey, null, $morphType);
    }

    /**
     * @param  array{source: class-string<Model>, type: string, relation?: ?string, foreignKey?: ?string, ownerKey?: ?string, morphType?: ?string, label?: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        $reference = new self(
            $data['source'],
            $data['type'],
            $data['relation'] ?? null,
            $data['foreignKey'] ?? null,
            $data['ownerKey'] ?? null,
            $data['morphType'] ?? null,
        );

        $reference->label = $data['label'] ?? null;

        return $reference;
    }

    /**
     * @return array{source: class-string<Model>, type: string, relation: ?string, foreignKey: ?string, ownerKey: ?string, morphType: ?string, label: ?string}
     */
    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'type' => $this->type,
            'relation' => $this->relation,
            'foreignKey' => $this->foreignKey,
            'ownerKey' => $this->ownerKey,
            'morphType' => $this->morphType,
            'label' => $this->label,
        ];
    }

    /**
     * Column on the source that stores the target key. Shorthand for a
     * custom reference that behaves like belongsTo.
     */
    public function via(string $foreignKey, string $ownerKey = 'id'): self
    {
        return self::belongsTo($this->source, $foreignKey, $foreignKey, $ownerKey)->label($this->label);
    }

    /**
     * @param  Closure(Builder<Model>, Model): Builder<Model>  $constraint
     */
    public function where(Closure $constraint): self
    {
        $this->constraint = $constraint;

        return $this;
    }

    public function label(?string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(int $count = 2): string
    {
        if ($this->label !== null) {
            return $this->label;
        }

        $resource = ResourceLocator::for($this->source);

        if ($resource !== null) {
            return $count === 1 ? $resource::getModelLabel() : $resource::getPluralModelLabel();
        }

        $label = Str::of(class_basename($this->source))->headline()->lower()->toString();

        return $count === 1 ? $label : Str::plural($label);
    }

    /**
     * Constrain a query on the source model to rows pointing at $target.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function apply(Builder $query, Model $target): Builder
    {
        return match ($this->type) {
            self::TYPE_BELONGS_TO => $query->where(
                $query->qualifyColumn((string) $this->foreignKey),
                $target->getAttribute((string) $this->ownerKey),
            ),
            self::TYPE_MORPH_TO => $query
                ->where($query->qualifyColumn((string) $this->morphType), $target->getMorphClass())
                ->where($query->qualifyColumn((string) $this->foreignKey), $target->getKey()),
            default => $this->constraint !== null
                ? ($this->constraint)($query, $target)
                : $query->whereRaw('1 = 0'),
        };
    }

    /**
     * Constrain a query to rows pointing at any of $targets in one statement.
     * Custom references have no batch form and return null.
     *
     * @param  Builder<Model>  $query
     * @param  Collection<int, Model>  $targets
     * @return Builder<Model>|null
     */
    public function applyMany(Builder $query, Collection $targets): ?Builder
    {
        return match ($this->type) {
            self::TYPE_BELONGS_TO => $query->whereIn(
                $query->qualifyColumn((string) $this->foreignKey),
                $targets->map(fn (Model $target): mixed => $target->getAttribute((string) $this->ownerKey))->all(),
            ),
            self::TYPE_MORPH_TO => $query->where(function (Builder $query) use ($targets): void {
                foreach ($targets->groupBy(fn (Model $target): string => $target->getMorphClass()) as $morphClass => $group) {
                    $query->orWhere(fn (Builder $query): Builder => $query
                        ->where($query->qualifyColumn((string) $this->morphType), $morphClass)
                        ->whereIn($query->qualifyColumn((string) $this->foreignKey), $group->map(fn (Model $target): mixed => $target->getKey())->all()));
                }
            }),
            default => null,
        };
    }

    /**
     * Stable identity: the same reference resolved for two records must share
     * a key so bulk totals merge. The map assigns keys to custom references.
     */
    public function key(): string
    {
        return $this->key ?? $this->source . '@' . ($this->relation ?? $this->foreignKey ?? 'custom');
    }

    /**
     * @internal
     */
    public function withKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }
}
