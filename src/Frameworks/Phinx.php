<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Frameworks;

use SpykraLabs\Alba\Support\Context;
use SpykraLabs\Alba\Support\Database;
use SpykraLabs\Alba\Tasks\Task;

/**
 * Phinx (robmorgan/phinx), the standard migration tool for Slim, Mezzio,
 * Laminas and plain PHP apps. Your phinx config should read the DB_* env
 * values written here (for example with getenv() or $_ENV in phinx.php).
 */
final class Phinx extends Framework
{
    public function __construct(private string $environment = 'production', private ?string $config = null) {}

    public function name(): string
    {
        return 'phinx';
    }

    public function detect(string $basePath): bool
    {
        return is_file($basePath.'/vendor/bin/phinx') && (is_file($basePath.'/phinx.php') || is_file($basePath.'/phinx.yml'));
    }

    public function writeDatabase(Context $ctx, array $db): void
    {
        $this->writeEnv($ctx, [
            'DB_CONNECTION' => $db['driver'],
            'DB_HOST' => $db['host'],
            'DB_PORT' => $db['port'],
            'DB_DATABASE' => $db['driver'] === 'sqlite' ? Database::sqlitePath($db['database'], $ctx) : $db['database'],
            'DB_USERNAME' => $db['username'],
            'DB_PASSWORD' => $db['password'],
        ]);
    }

    public function migrate(): Task
    {
        return $this->phinx(['migrate'], 'Run migrations');
    }

    public function seed(): Task
    {
        return $this->phinx(['seed:run'], 'Run seeders');
    }

    /** @param list<string> $args */
    public function phinx(array $args, ?string $name = null): Task
    {
        $options = ['-e', $this->environment, ...($this->config ? ['-c', $this->config] : []), '--no-interaction'];

        return $this->cli('vendor/bin/phinx', [...$args, ...$options], $name ?? 'phinx '.implode(' ', $args));
    }

    public function requirements(): array
    {
        return ['php' => '8.1.0', 'extensions' => ['pdo'], 'writable' => ['.env']];
    }
}
