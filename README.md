# Filament Where Used

Show who references a record and make delete safe.

Before an admin deletes something, the panel tells them who still depends on it: **"Used by 14 products and 3 promotions."** The delete is blocked (or allowed after an explicit confirmation), and every record's View or Edit page can show a **References** widget listing the records that point at it, each one clickable.

The plugin discovers relationships itself by reflecting your models' `BelongsTo` and `MorphTo` methods, so there is nothing to declare for the common cases.

## Requirements

- PHP 8.2+
- Filament 4.x or 5.x

## Installation

```bash
composer require nsumbadze/filament-where-used
```

Register the plugin in your panel provider:

```php
use Nsumbadze\WhereUsed\WhereUsedPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(WhereUsedPlugin::make());
}
```

Optionally publish the config:

```bash
php artisan vendor:publish --tag=filament-where-used-config
```

## What you get out of the box

Once the plugin is registered, every `DeleteAction` and `DeleteBulkAction` in the panel:

- shows the usage summary in the confirmation modal,
- disables the confirm button and cancels the action when references exist (`block` mode, the default), or
- lets the user proceed after reading the summary (`confirm` mode).

```php
WhereUsedPlugin::make()
    ->onDelete('confirm')                 // 'block' (default) or 'confirm'
    ->modelPaths([app_path('Models')])    // where to look for models
    ->ignore([ActivityLog::class]);       // models that never count as references
```

## References widget

Add the widget to any View or Edit page. Resource pages pass the record automatically.

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

Each referencing model gets a card with the total count and the first few records (configurable with `widget_limit`). Links open the View page when the user may view the record, else the Edit page, else no link.

## Custom references

The scanner sees `BelongsTo` and `MorphTo` methods with a declared return type. For anything else (JSON columns, external keys), implement `HasReferences` on the target model:

```php
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

## Using the counts yourself

```php
use Nsumbadze\WhereUsed\Facades\WhereUsed;

WhereUsed::isReferenced($category);                 // bool, stops at the first hit
WhereUsed::usagesOf($category);                     // Collection<ReferenceCount>, non-zero only
WhereUsed::usagesAcross($categories);               // totals for many records, one query per reference
WhereUsed::summary(WhereUsed::usagesOf($category)); // "Used by 14 products and 3 promotions."
```

Counts run through the referencing model's Filament resource query (`getEloquentQuery()`) when a resource exists, so tenancy and soft-delete scopes match what the panel shows: soft-deleted referencing rows do not count unless the resource query includes trashed records.

Counts are memoised per record for the request (the delete modal asks three times: description, submit button, guard). Call `WhereUsed::flush()` after changing references in the same request.

## Caching the reference map

Discovery reflects your models once and caches the result forever. Rebuild it on deploy:

```bash
php artisan where-used:cache
php artisan where-used:clear
```

## Notes

- Filament keeps a single `->before()` hook per action. If a resource sets its own `before()` on a delete action, it replaces the guard; call `Nsumbadze\WhereUsed\DeleteGuard::apply($action)` after your own configuration or use `WhereUsed::isReferenced()` inside your hook.
- Untyped relation methods are skipped by default because discovery has to call them. Enable `discover_untyped` if all your relation methods are side-effect free.
- Polymorphic references use the target's `getMorphClass()`, so morph maps are respected.

## Testing

```bash
composer test
composer analyse
composer format
```

## Licence

MIT.
