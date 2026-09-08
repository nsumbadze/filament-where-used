<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed;

use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Components\ComponentManager;
use Filament\Support\Components\Contracts\ScopedComponentManager;
use Filament\Support\Concerns\EvaluatesClosures;
use Nsumbadze\WhereUsed\Enums\DeleteBehaviour;
use Throwable;

class WhereUsedPlugin implements Plugin
{
    use EvaluatesClosures;

    public const ID = 'where-used';

    /**
     * configureUsing() registers on the process-wide ComponentManager, so the
     * guard is attached once per manager no matter how many panels register
     * the plugin (and again after the container is rebuilt, e.g. in tests).
     */
    protected static ?ScopedComponentManager $guardsConfiguredFor = null;

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
        $plugin = filament(self::ID);

        return $plugin;
    }

    /**
     * Behaviour for the current panel, falling back to config when the panel
     * did not register the plugin (the delete guard is process-wide).
     */
    public static function currentBehaviour(): DeleteBehaviour
    {
        try {
            if (Filament::getCurrentOrDefaultPanel()?->hasPlugin(self::ID)) {
                return static::get()->getDeleteBehaviour();
            }
        } catch (Throwable) {
            // No panel in this context (console, queue): use the config default.
        }

        return DeleteBehaviour::from((string) config('filament-where-used.on_delete', 'block'));
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function onDelete(DeleteBehaviour|string|Closure $behaviour): static
    {
        $this->onDelete = $behaviour;

        return $this;
    }

    public function getDeleteBehaviour(): DeleteBehaviour
    {
        $behaviour = $this->evaluate($this->onDelete) ?? config('filament-where-used.on_delete', 'block');

        return $behaviour instanceof DeleteBehaviour ? $behaviour : DeleteBehaviour::from((string) $behaviour);
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

        $manager = ComponentManager::resolve();

        if (! $this->guardsDeleteActions || static::$guardsConfiguredFor === $manager) {
            return;
        }

        static::$guardsConfiguredFor = $manager;

        DeleteAction::configureUsing(fn (DeleteAction $action) => DeleteGuard::apply($action));
        DeleteBulkAction::configureUsing(fn (DeleteBulkAction $action) => DeleteGuard::applyToBulk($action));
    }
}
