<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Frameworks;

use SpykraLabs\Alba\Support\Context;
use SpykraLabs\Alba\Support\Database;
use SpykraLabs\Alba\Tasks\{CopyFileTask, Task, WriteEnvTask};

/** CodeIgniter 4. Works through `php spark`; settings go to `.env`. */
final class CodeIgniter extends Framework
{
    public function name(): string
    {
        return 'codeigniter';
    }

    public function detect(string $basePath): bool
    {
        return is_file($basePath.'/spark') && $this->hasComposerPackage($basePath, 'codeigniter4/framework');
    }

    public function writeDatabase(Context $ctx, array $db): void
    {
        $driver = ['mysql' => 'MySQLi', 'pgsql' => 'Postgre', 'sqlite' => 'SQLite3'][$db['driver']];
        $sqlite = $db['driver'] === 'sqlite';

        $this->writeEnv($ctx, array_filter([
            'database.default.DBDriver' => $driver,
            'database.default.hostname' => $sqlite ? null : $db['host'],
            'database.default.port' => $sqlite ? null : $db['port'],
            'database.default.database' => $sqlite ? Database::sqlitePath($db['database'], $ctx) : $db['database'],
            'database.default.username' => $sqlite ? null : $db['username'],
            'database.default.password' => $sqlite ? null : $db['password'],
        ], fn ($v) => $v !== null));
    }

    /** CI4 ships an `env` template file; this turns it into `.env` if missing. */
    public function copyEnvTemplate(): Task
    {
        return new CopyFileTask('env', '.env', true, 'Create .env from env template');
    }

    public function migrate(): Task
    {
        return $this->spark(['migrate', '--all'], 'Run migrations');
    }

    public function seed(?string $seeder = null): Task
    {
        return $this->spark(['db:seed', $seeder ?? 'DatabaseSeeder'], 'Run seeders');
    }

    /** Generates the encryption key without spark. */
    public function generateKey(): Task
    {
        return new WriteEnvTask(['encryption.key' => 'hex2bin:{{ random.hex:32 }}'], null, 'Generate encryption key', keepExisting: true);
    }

    public function production(string $baseUrlPlaceholder = '{{ answers.app_url }}'): Task
    {
        return new WriteEnvTask(
            ['CI_ENVIRONMENT' => 'production', 'app.baseURL' => $baseUrlPlaceholder],
            null,
            'Set production mode and base URL',
        );
    }

    public function clearCache(): Task
    {
        return $this->spark(['cache:clear'], 'Clear cache');
    }

    /** @param list<string> $args */
    public function spark(array $args, ?string $name = null): Task
    {
        return $this->cli('spark', [...$args, '--no-interaction'], $name ?? 'spark '.implode(' ', $args));
    }

    public function finalize(): array
    {
        return [$this->generateKey(), $this->clearCache()];
    }

    public function requirements(): array
    {
        return [
            'php' => '8.1.0',
            'extensions' => ['intl', 'json', 'mbstring', 'mysqlnd', 'libcurl'],
            'writable' => ['writable', '.env'],
        ];
    }
}
