<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed;

use Nsumbadze\WhereUsed\Contracts\HasReferences;
use Nsumbadze\WhereUsed\Support\ModelDiscovery;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

/**
 * Inverse relation map: for every model, the models that point at it.
 *
 * Built once by reflecting BelongsTo / MorphTo relation methods, cached, and
 * rebuilt with `where-used:cache`. Custom references declared through
 * HasReferences are read at runtime and never cached (they hold closures).
 */
class ReferenceMap
{
    public const MORPH_KEY = '*';

    /** @var array<int, string> */
    protected array $paths = [];

    /** @var array<int, class-string> */
    protected array $ignored = [];

    /** @var array<string, array<int, array<string, mixed>>>|null */
    protected ?array $map = null;

    /**
     * @param  array<int, string>  $paths
     */
    public function usePaths(array $paths): static
    {
        $this->paths = $paths;

        return $this;
    }

    /**
     * @param  array<int, class-string>  $models
     */
    public function ignore(array $models): static
    {
        $this->ignored = $models;

        return $this;
    }

    /**
     * References pointing at the given model class.
     *
     * @param  class-string<Model>  $target
     * @return array<int, Reference>
     */
    public function for(string $target): array
    {
        $map = $this->all();

        $references = array_map(
            Reference::fromArray(...),
            [...($map[$target] ?? []), ...($map[self::MORPH_KEY] ?? [])],
        );

        if (is_subclass_of($target, HasReferences::class)) {
            $references = [...$references, ...$target::references()];
        }

        return array_values(array_filter(
            $references,
            fn (Reference $reference): bool => ! in_array($reference->source, $this->ignored, true),
        ));
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function all(): array
    {
        if ($this->map !== null) {
            return $this->map;
        }

        $cached = $this->cache()->get($this->cacheKey());

        if (is_array($cached)) {
            return $this->map = $cached;
        }

        return $this->map = $this->rebuild();
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function rebuild(): array
    {
        $this->map = $this->build();

        $this->cache()->forever($this->cacheKey(), $this->map);

        return $this->map;
    }

    public function clear(): void
    {
        $this->map = null;

        $this->cache()->forget($this->cacheKey());
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function build(): array
    {
        $map = [];
        $discoverUntyped = (bool) config('filament-where-used.discover_untyped', false);

        foreach (ModelDiscovery::classesIn($this->paths) as $source) {
            foreach ($this->relationMethods($source, $discoverUntyped) as $method) {
                $relation = $this->instantiate($source, $method);

                if ($relation instanceof MorphTo) {
                    $map[self::MORPH_KEY][] = Reference::morphTo(
                        $source,
                        $method->getName(),
                        $relation->getForeignKeyName(),
                        $relation->getMorphType(),
                    )->toArray();

                    continue;
                }

                if ($relation instanceof BelongsTo) {
                    $map[$relation->getRelated()::class][] = Reference::belongsTo(
                        $source,
                        $method->getName(),
                        $relation->getForeignKeyName(),
                        $relation->getOwnerKeyName(),
                    )->toArray();
                }
            }
        }

        ksort($map);

        return $map;
    }

    /**
     * @param  class-string<Model>  $model
     * @return array<int, ReflectionMethod>
     */
    protected function relationMethods(string $model, bool $includeUntyped): array
    {
        $methods = [];

        foreach ((new ReflectionClass($model))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || $method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            if ($method->getDeclaringClass()->getName() === Model::class) {
                continue;
            }

            $type = $method->getReturnType();

            if ($type === null) {
                if ($includeUntyped) {
                    $methods[] = $method;
                }

                continue;
            }

            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $name = $type->getName();

            if ($name === BelongsTo::class || $name === MorphTo::class || $name === Relation::class) {
                $methods[] = $method;
            }
        }

        return $methods;
    }

    /**
     * @param  class-string<Model>  $model
     * @return Relation<Model, Model, mixed>|null
     */
    protected function instantiate(string $model, ReflectionMethod $method): ?Relation
    {
        try {
            $relation = $method->invoke(new $model);
        } catch (Throwable) {
            return null;
        }

        return $relation instanceof Relation ? $relation : null;
    }

    protected function cache(): Repository
    {
        return Cache::store(config('filament-where-used.cache.store'));
    }

    protected function cacheKey(): string
    {
        return (string) config('filament-where-used.cache.key', 'filament-where-used.map');
    }
}
