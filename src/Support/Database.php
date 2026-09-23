<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Support;

use InvalidArgumentException;
use PDO;

final class Database
{
    /** @param array<string, string> $db */
    public static function connect(array $db, Context $ctx): PDO
    {
        $driver = $db['driver'] ?? '';
        $dsn = match ($driver) {
            'mysql' => "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset=utf8mb4",
            'pgsql' => "pgsql:host={$db['host']};port={$db['port']};dbname={$db['database']}",
            'sqlite' => 'sqlite:'.self::sqlitePath($db['database'], $ctx),
            default => throw new InvalidArgumentException("Unsupported database driver [$driver]."),
        };

        return new PDO($dsn, $db['username'] ?? null, $db['password'] ?? null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
    }

    /** A DSN-style URL (mysql://user:pass@host:port/db), as used by Symfony/Doctrine and others. */
    public static function url(array $db, Context $ctx): string
    {
        if (($db['driver'] ?? '') === 'sqlite') {
            return 'sqlite:///'.self::sqlitePath($db['database'], $ctx);
        }
        $scheme = $db['driver'] === 'pgsql' ? 'postgresql' : 'mysql';
        $auth = rawurlencode($db['username'] ?? '').($db['password'] !== '' ? ':'.rawurlencode($db['password']) : '');

        return "$scheme://$auth@{$db['host']}:{$db['port']}/{$db['database']}";
    }

    public static function sqlitePath(string $database, Context $ctx): string
    {
        $path = $ctx->path($database);
        if (! is_dir(dirname($path))) {
            @mkdir(dirname($path), 0775, true);
        }

        return $path;
    }
}
