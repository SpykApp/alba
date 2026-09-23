<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Frameworks;

use SpykraLabs\Alba\Support\Context;
use SpykraLabs\Alba\Support\Database;
use SpykraLabs\Alba\Tasks\{CopyFileTask, Task, WriteEnvTask};

/** Laravel (and Lumen-style artisan apps). Works through `php artisan`. */
final class Laravel extends Framework
{
    public function name(): string
    {
        return 'laravel';
    }

    public function detect(string $basePath): bool
    {
        return is_file($basePath.'/artisan') && $this->hasComposerPackage($basePath, 'laravel/framework');
    }

    public function writeDatabase(Context $ctx, array $db): void
    {
        $sqlite = $db['driver'] === 'sqlite';
        $this->writeEnv($ctx, [
            'DB_CONNECTION' => $db['driver'],
            'DB_HOST' => $db['host'],
            'DB_PORT' => $db['port'],
            'DB_DATABASE' => $sqlite ? Database::sqlitePath($db['database'], $ctx) : $db['database'],
            'DB_USERNAME' => $db['username'],
            'DB_PASSWORD' => $db['password'],
        ]);
    }

    /** `php artisan migrate --force` (or `migrate:fresh` when $fresh). */
    public function migrate(bool $fresh = false): Task
    {
        return $this->artisan([$fresh ? 'migrate:fresh' : 'migrate', '--force'], 'Run migrations');
    }

    /** `php artisan db:seed --force`, optionally a specific seeder class. */
    public function seed(?string $class = null): Task
    {
        return $this->artisan(['db:seed', '--force', ...($class ? ["--class=$class"] : [])], 'Run seeders');
    }

    /** Generates APP_KEY in .env without artisan, only when it is empty. */
    public function generateKey(): Task
    {
        return new WriteEnvTask(['APP_KEY' => 'base64:{{ random.base64:32 }}'], null, 'Generate application key', keepExisting: true);
    }

    public function copyEnvExample(): Task
    {
        return new CopyFileTask('.env.example', '.env', true, 'Create .env from .env.example');
    }

    public function storageLink(): Task
    {
        return $this->artisan(['storage:link', '--force'], 'Link storage');
    }

    public function clearCaches(): Task
    {
        return $this->artisan(['optimize:clear'], 'Clear caches');
    }

    public function optimize(): Task
    {
        return $this->artisan(['optimize'], 'Optimize for production');
    }

    /** Any artisan command, e.g. artisan(['vendor:publish', '--tag=assets']). @param list<string> $args */
    public function artisan(array $args, ?string $name = null): Task
    {
        return $this->cli('artisan', [...$args, '--no-interaction'], $name ?? 'php artisan '.implode(' ', $args));
    }

    public function finalize(): array
    {
        return [$this->generateKey(), $this->storageLink(), $this->clearCaches()];
    }

    public function requirements(): array
    {
        return [
            'php' => '8.2.0',
            'extensions' => ['ctype', 'curl', 'dom', 'fileinfo', 'filter', 'hash', 'mbstring', 'openssl', 'pcre', 'pdo', 'session', 'tokenizer', 'xml'],
            'writable' => ['storage', 'bootstrap/cache', '.env'],
        ];
    }
}
