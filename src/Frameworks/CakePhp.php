<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Frameworks;

use SpykraLabs\Alba\Support\ConfigFile;
use SpykraLabs\Alba\Support\Context;
use SpykraLabs\Alba\Support\Database;
use SpykraLabs\Alba\Tasks\Task;

/** CakePHP 4/5. Works through `bin/cake`; settings go to `config/app_local.php`. */
final class CakePhp extends Framework
{
    public function name(): string
    {
        return 'cakephp';
    }

    public function detect(string $basePath): bool
    {
        return is_file($basePath.'/bin/cake') && $this->hasComposerPackage($basePath, 'cakephp/cakephp');
    }

    /**
     * Writes config/app_local.php with the datasource, a fresh Security.salt and
     * debug off. This replaces the file, so keep other local overrides elsewhere.
     */
    public function writeDatabase(Context $ctx, array $db): void
    {
        $driver = ['mysql' => 'Mysql', 'pgsql' => 'Postgres', 'sqlite' => 'Sqlite'][$db['driver']];
        $sqlite = $db['driver'] === 'sqlite';

        ConfigFile::writePhpArray($ctx, 'config/app_local.php', [
            'debug' => false,
            'Security' => ['salt' => bin2hex(random_bytes(32))],
            'Datasources' => ['default' => array_filter([
                'driver' => 'Cake\\Database\\Driver\\'.$driver,
                'host' => $sqlite ? null : $db['host'],
                'port' => $sqlite ? null : (int) $db['port'],
                'username' => $sqlite ? null : $db['username'],
                'password' => $sqlite ? null : $db['password'],
                'database' => $sqlite ? Database::sqlitePath($db['database'], $ctx) : $db['database'],
            ], fn ($v) => $v !== null)],
        ]);
    }

    /** Needs cakephp/migrations. */
    public function migrate(): Task
    {
        return $this->cake(['migrations', 'migrate'], 'Run migrations');
    }

    /** Needs cakephp/migrations seeds in config/Seeds. */
    public function seed(): Task
    {
        return $this->cake(['migrations', 'seed'], 'Run seeders');
    }

    public function clearCache(): Task
    {
        return $this->cake(['cache', 'clear_all'], 'Clear caches');
    }

    /** @param list<string> $args */
    public function cake(array $args, ?string $name = null): Task
    {
        return $this->cli('bin/cake', $args, $name ?? 'bin/cake '.implode(' ', $args));
    }

    public function finalize(): array
    {
        return [$this->clearCache()];
    }

    public function requirements(): array
    {
        return [
            'php' => '8.1.0',
            'extensions' => ['intl', 'mbstring', 'pdo', 'simplexml'],
            'writable' => ['tmp', 'logs', 'config'],
        ];
    }
}
