<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use ReflectionClass;

final class ModelDiscovery
{
    /**
     * Fully qualified names of every concrete Eloquent model under $paths.
     *
     * @param  array<int, string>  $paths
     * @return array<int, class-string<Model>>
     */
    public static function classesIn(array $paths): array
    {
        $classes = [];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            foreach (File::allFiles($path) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $class = self::classNameOf($file->getPathname());

                if ($class === null || ! class_exists($class)) {
                    continue;
                }

                if (! is_subclass_of($class, Model::class)) {
                    continue;
                }

                if ((new ReflectionClass($class))->isAbstract()) {
                    continue;
                }

                $classes[] = $class;
            }
        }

        sort($classes);

        return array_values(array_unique($classes));
    }

    /**
     * @return class-string|null
     */
    public static function classNameOf(string $file): ?string
    {
        $contents = (string) file_get_contents($file);

        if (! preg_match('/^\s*namespace\s+([^;]+);/m', $contents, $namespace)) {
            return null;
        }

        if (preg_match('/^\s*(?:enum|interface|trait)\s+\w+/m', $contents)) {
            return null;
        }

        if (! preg_match('/^\s*(?:final\s+|abstract\s+|readonly\s+)*class\s+(\w+)/m', $contents, $class)) {
            return null;
        }

        /** @var class-string $name */
        $name = trim($namespace[1]) . '\\' . $class[1];

        return $name;
    }
}
