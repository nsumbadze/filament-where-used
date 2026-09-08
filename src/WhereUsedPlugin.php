<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed;

use Closure;
use Nsumbadze\WhereUsed\Enums\DeleteBehaviour;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;

class WhereUsedPlugin implements Plugin
{
    use EvaluatesClosures;

    protected DeleteBehaviour|string|Closure|null $onDelete = null;

    /** @var array<int, string> | null */
    protected ?array $modelPaths = null;

    /** @var array<int, class-string> */
    protected array $ignore = [];

    protected bool $guardsDeleteActions = true;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'where-used';
    }

    public function onDelete(DeleteBehaviour|string|Closure $behaviour): static
    {
        $this->onDelete = $behaviour;

        return $this;
    }

    public function getDeleteBehaviour(): DeleteBehaviour
    {
        $behaviour = $this->evaluate($this->onDelete) ?? config('filament-where-used.on_delete', 'block');

        return $behaviour instanceof DeleteBehaviour ? $behaviour : DeleteBehaviour::from($behaviour);
    }

    /**
     * @param  array<int, string>  $paths
     */
    public function modelPaths(array $paths): static
    {
        $this->modelPaths = $paths;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getModelPaths(): array
    {
        return $this->modelPaths ?? config('filament-where-used.model_paths', []);
    }

    /**
     * @param  array<int, class-string>  $models
     */
    public function ignore(array $models): static
    {
        $this->ignore = [...$this->ignore, ...$models];

        return $this;
    }

    /**
     * @return array<int, class-string>
     */
    public function getIgnoredModels(): array
    {
        return array_values(array_unique([...config('filament-where-used.ignore', []), ...$this->ignore]));
    }

    public function guardDeleteActions(bool $condition = true): static
    {
        $this->guardsDeleteActions = $condition;

        return $this;
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        $map = app(ReferenceMap::class);
        $map->usePaths($this->getModelPaths());
        $map->ignore($this->getIgnoredModels());

        if (! $this->guardsDeleteActions) {
            return;
        }

        DeleteAction::configureUsing(fn (DeleteAction $action) => DeleteGuard::apply($action));
        DeleteBulkAction::configureUsing(fn (DeleteBulkAction $action) => DeleteGuard::applyToBulk($action));
    }
}
