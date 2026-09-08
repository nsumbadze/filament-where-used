<?php

declare(strict_types=1);
use Larastan\Larastan\ApplicationResolver;

/*
 * Larastan validates `view-string` literals against a booted application that
 * does not load this package's service provider, so its view namespace has to
 * be registered by hand before analysis starts.
 */
$app = ApplicationResolver::resolve();

$app->make('view')->addNamespace('filament-where-used', __DIR__ . '/resources/views');
