<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Themes;

use SpykraLabs\Alba\Alba;

/** Turns theme tokens into the CSS variable block injected into every page. */
final class Tokens
{
    /** The variables the default stylesheet understands. */
    public const NAMES = [
        'bg', 'fg', 'muted', 'muted-fg', 'border', 'brand', 'brand-fg',
        'ok', 'bad', 'radius', 'font', 'mono',
    ];

    public static function css(Alba $alba): string
    {
        $pack = $alba->themePack?->tokens() ?? [];
        $shared = ($pack['shared'] ?? []) + array_filter([
            'brand' => $alba->theme['brand'] ?? null,
            'radius' => $alba->theme['radius'] ?? null,
            'font' => $alba->theme['font'] ?? null,
        ]);
        $light = $pack['light'] ?? [];
        $dark = $pack['dark'] ?? [];

        // Explicit brand override must win in both modes.
        foreach (['brand', 'radius', 'font'] as $key) {
            if (isset($alba->theme[$key])) {
                unset($light[$key], $dark[$key]);
            }
        }

        $css = '';
        if ($shared || $light) {
            $css .= ':root{'.self::block($shared + $light).'}';
            $css .= ':root[data-theme="light"]{'.self::block($light).'}';
        }
        if ($dark) {
            $css .= '@media (prefers-color-scheme:dark){:root:not([data-theme="light"]){'.self::block($dark).'}}';
            $css .= ':root[data-theme="dark"]{'.self::block($dark).'}';
        }

        return $css;
    }

    /** @param array<string, string> $tokens */
    private static function block(array $tokens): string
    {
        $out = '';
        foreach ($tokens as $name => $value) {
            // Refuse anything that could break out of a declaration.
            if (preg_match('/^[a-z0-9-]+$/', (string) $name) && ! preg_match('/[;{}<>\\\\]/', (string) $value)) {
                $out .= "--alba-$name:$value;";
            }
        }

        return $out;
    }
}
