<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Support;

/**
 * Resolves {{ placeholders }} in templates, env values and config arrays.
 *
 *   {{ db.host }} {{ db.database }} {{ db.url }} {{ db.path }} (absolute sqlite path)
 *   {{ answers.app_name }}   {{ license.type }}   {{ license.meta.buyer }}
 *   {{ path.base }}          {{ app.route }}
 *   {{ random.hex:32 }} {{ random.base64:32 }} {{ random.alnum:64 }}
 *
 * Filters: {{ answers.name|php }} (quoted PHP string), |json, |url, |base64.
 */
final class Placeholders
{
    public static function resolve(string $template, Context $ctx): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_.:\-]+)\s*(?:\|\s*(\w+))?\s*\}\}/',
            fn (array $m) => self::filter(self::value($m[1], $ctx), $m[2] ?? null),
            $template,
        );
    }

    /** Resolve every string inside a (nested) array. */
    public static function resolveArray(array $data, Context $ctx): array
    {
        foreach ($data as $key => $value) {
            $data[$key] = is_array($value) ? self::resolveArray($value, $ctx)
                : (is_string($value) ? self::resolve($value, $ctx) : $value);
        }

        return $data;
    }

    private static function value(string $key, Context $ctx): string
    {
        if (str_starts_with($key, 'random.')) {
            [$kind, $length] = array_pad(explode(':', substr($key, 7), 2), 2, '32');
            $n = max(1, (int) $length);

            return match ($kind) {
                'hex' => bin2hex(random_bytes($n)),
                'base64' => base64_encode(random_bytes($n)),
                'alnum' => substr(str_repeat('', 0).self::alnum($n), 0, $n),
                default => '',
            };
        }

        $db = $ctx->state->get('db', []);
        $roots = [
            'db' => $db + ($db ? ['url' => Database::url($db, $ctx), 'path' => $db['driver'] === 'sqlite' ? Database::sqlitePath($db['database'], $ctx) : ''] : []),
            'answers' => $ctx->state->get('answers', []),
            'license' => $ctx->license() ?? [],
            'path' => ['base' => $ctx->alba->basePath],
            'app' => ['route' => $ctx->alba->route],
        ];

        $value = $roots;
        foreach (explode('.', $key) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return '';
            }
            $value = $value[$segment];
        }

        return is_scalar($value) ? (string) $value : '';
    }

    private static function alnum(int $length): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $chars[random_int(0, 61)];
        }

        return $out;
    }

    private static function filter(string $value, ?string $filter): string
    {
        return match ($filter) {
            'php' => var_export($value, true),
            'json' => (string) json_encode($value),
            'url' => rawurlencode($value),
            'base64' => base64_encode($value),
            default => $value,
        };
    }
}
