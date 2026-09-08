<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Model discovery
    |--------------------------------------------------------------------------
    |
    | Directories scanned for Eloquent models. Every public method on a model
    | whose declared return type is BelongsTo or MorphTo becomes a reference.
    |
    */
    'model_paths' => [
        app_path('Models'),
    ],

    /*
    | Also call untyped relation methods during discovery. Off by default:
    | invoking arbitrary methods on a fresh model can have side effects.
    */
    'discover_untyped' => false,

    /*
    | Models that must never be treated as referencing another record
    | (activity logs, audit tables, …).
    */
    'ignore' => [],

    /*
    |--------------------------------------------------------------------------
    | Delete behaviour
    |--------------------------------------------------------------------------
    |
    | "block"   – the delete action is cancelled while references exist.
    | "confirm" – the modal shows the references; the user may still delete.
    |
    */
    'on_delete' => 'block',

    /*
    | Cache store and key for the discovered reference map. Rebuild with
    | `php artisan where-used:cache` (run it on deploy).
    */
    'cache' => [
        'store' => null,
        'key' => 'filament-where-used.map',
    ],

    /*
    | How many referencing records the References widget lists per model.
    */
    'widget_limit' => 5,

];
