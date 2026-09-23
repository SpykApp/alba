<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Frameworks;

use InvalidArgumentException;

/** Registry and auto-detection of the built-in presets. */
final class Frameworks
{
    /** @return array<string, class-string<Framework>> */
    public static function all(): array
    {
        return [
            'laravel' => Laravel::class,
            'symfony' => Symfony::class,
            'codeigniter' => CodeIgniter::class,
            'yii2' => Yii2::class,
            'cakephp' => CakePhp::class,
            'wordpress' => WordPress::class,
            'drupal' => Drupal::class,
            'phinx' => Phinx::class,
            'doctrine' => DoctrineMigrations::class,
        ];
    }

    public static function named(string $name): Framework
    {
        $class = self::all()[strtolower($name)] ?? throw new InvalidArgumentException(
            "Unknown framework [$name]. Available: ".implode(', ', array_keys(self::all())),
        );

        return new $class;
    }

    /** First preset whose detect() matches, or null. */
    public static function detect(string $basePath): ?Framework
    {
        foreach (self::all() as $class) {
            $framework = new $class;
            if ($framework->detect($basePath)) {
                return $framework;
            }
        }

        return null;
    }
}
