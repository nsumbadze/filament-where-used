# Filament Where Used

Shows which records reference a record before it is deleted, and blocks or confirms the deletion accordingly.

The plugin reflects the `BelongsTo` and `MorphTo` relationships of your models once, caches the result, and uses it to answer "what points at this record?". Every `DeleteAction` and `DeleteBulkAction` in the panel gets the answer in its confirmation modal, for example "Used by 14 products and 3 promotions." An optional widget lists the referencing records on View and Edit pages.

## Requirements

- PHP 8.2 or newer (8.3 for Filament 5)
- Filament 4 or 5

## Installation

```bash
composer require nsumbadze/filament-where-used
```

Register the plugin in the panel provider:

```php
use Nsumbadze\WhereUsed\WhereUsedPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(WhereUsedPlugin::make());
}
```

The config file can be published with `php artisan vendor:publish --tag=filament-where-used-config`.

## Delete guards

With the plugin registered, delete actions behave as follows when references exist:

- `block` (default): the modal shows the summary, the confirm button is disabled, and the action is cancelled on the server if it is submitted anyway.
- `confirm`: the modal shows the summary and the user can still delete.

```php
WhereUsedPlugin::make()
    ->onDelete('confirm')
    ->modelPaths([app_path('Models')])
    ->ignore([ActivityLog::class])
    ->guardDeleteActions(false); // keep discovery and the widget, do not touch delete actions
```

Filament stores one `before()` hook per action. If a resource defines its own `before()` on a delete action, that hook replaces the guard's. In that case call `DeleteGuard::guard($action, collect([$record]))` from your hook, or check `WhereUsed::isReferenced($record)` yourself. The modal description and the disabled button do not depend on the hook.

The guards are attached with `DeleteAction::configureUsing()`, which applies process-wide. Panels that do not register the plugin use the `on_delete` value from the config file.

## References widget

```php
use Nsumbadze\WhereUsed\Widgets\ReferencesWidget;

class ViewCategory extends ViewRecord
{
    protected function getFooterWidgets(): array
    {
        return [ReferencesWidget::class];
    }
}
```

Resource pages pass the record to the widget automatically. Each referencing model is shown with its total and the first `widget_limit` records. A record links to its View page when the current user may view it, otherwise to its Edit page, otherwise it is shown as text.

## Custom references

Discovery only sees relation methods with a `BelongsTo` or `MorphTo` return type. Other cases can be declared on the target model:

```php
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Nsumbadze\WhereUsed\Contracts\HasReferences;
use Nsumbadze\WhereUsed\Reference;

class Category extends Model implements HasReferences
{
    public static function references(): array
    {
        return [
            Reference::make(Banner::class)
                ->label('banners')
                ->where(fn (Builder $query, Model $category) => $query->whereJsonContains('category_ids', $category->getKey())),

            Reference::make(LegacyOrder::class)->via('cat_id'),
        ];
    }
}
```

## Using the counts directly

```php
use Nsumbadze\WhereUsed\Facades\WhereUsed;

WhereUsed::isReferenced($category);                 // bool
WhereUsed::usagesOf($category);                     // references with a count above zero
WhereUsed::countsFor($category);                    // every reference, including zero
WhereUsed::usagesAcross($categories);               // totals for many records
WhereUsed::summary(WhereUsed::usagesOf($category)); // "Used by 14 products and 3 promotions."
```

Counts are executed through the referencing model's Filament resource query when a resource exists, so tenancy and soft-delete scopes match the panel. Soft-deleted referencing rows are not counted unless the resource query includes trashed records.

Results are memoised per record for the current request. `WhereUsed::flush()` clears the memo.

## Cache

The reference map is stored in the default cache store without expiry. Rebuild it on deploy:

```bash
php artisan where-used:cache
php artisan where-used:clear
```

## Configuration

| Key | Description |
| --- | --- |
| `model_paths` | Directories scanned for models |
| `discover_untyped` | Also call relation methods without a return type. Off by default, because discovery has to invoke them. |
| `ignore` | Models that never count as references |
| `on_delete` | `block` or `confirm` |
| `cache.store`, `cache.key` | Where the reference map is cached |
| `widget_limit` | Records listed per model in the widget |

## Testing

```bash
composer test
composer analyse
composer format
```

## License

MIT. See [LICENSE.md](LICENSE.md).
