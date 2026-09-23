<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Support;

/**
 * Request-scoped access to the active Translator, so steps and validators can
 * translate without carrying a translator around. The Kernel sets it once per
 * request.
 */
final class Lang
{
    private static ?Translator $current = null;

    public static function use(Translator $translator): void
    {
        self::$current = $translator;
    }

    /** @param array<string, scalar> $replace */
    public static function t(string $key, array $replace = [], ?string $default = null): string
    {
        return self::$current ? self::$current->get($key, $replace, $default) : strtr($default ?? $key, array_combine(
            array_map(fn ($k) => ':'.$k, array_keys($replace)),
            array_map('strval', $replace),
        ) ?: []);
    }

    /** Developer text: a string or a locale keyed array. */
    public static function text(string|array|null $value): string
    {
        return self::$current ? self::$current->text($value) : (is_array($value) ? (string) reset($value) : (string) $value);
    }

    public static function locale(): string
    {
        return self::$current?->locale ?? 'en';
    }
}
