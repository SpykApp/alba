<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Frameworks;

use SpykraLabs\Alba\Support\ConfigFile;
use SpykraLabs\Alba\Support\Context;
use SpykraLabs\Alba\Support\Database;
use SpykraLabs\Alba\Tasks\Task;

/** Yii 2 (basic and advanced templates). Works through the `yii` console script. */
final class Yii2 extends Framework
{
    public function __construct(private string $dbConfig = 'config/db.php', private string $console = 'yii') {}

    public function name(): string
    {
        return 'yii2';
    }

    public function detect(string $basePath): bool
    {
        return is_file($basePath.'/'.$this->console) && $this->hasComposerPackage($basePath, 'yiisoft/yii2');
    }

    /** Rewrites config/db.php (change the path in the constructor for the advanced template). */
    public function writeDatabase(Context $ctx, array $db): void
    {
        $dsn = match ($db['driver']) {
            'mysql' => "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']}",
            'pgsql' => "pgsql:host={$db['host']};port={$db['port']};dbname={$db['database']}",
            'sqlite' => 'sqlite:'.Database::sqlitePath($db['database'], $ctx),
        };

        ConfigFile::writePhpArray($ctx, $this->dbConfig, [
            'class' => 'yii\db\Connection',
            'dsn' => $dsn,
            'username' => $db['username'],
            'password' => $db['password'],
            'charset' => 'utf8',
        ]);
    }

    public function migrate(): Task
    {
        return $this->yii(['migrate/up', '--interactive=0'], 'Run migrations');
    }

    /** RBAC tables from yiisoft/yii2's own migrations. */
    public function migrateRbac(): Task
    {
        return $this->yii(['migrate/up', '--migrationPath=@yii/rbac/migrations', '--interactive=0'], 'Create RBAC tables');
    }

    /** Loads fixtures with `fixture/load` (all fixtures unless you pass names). @param list<string> $fixtures */
    public function seed(array $fixtures = ['*']): Task
    {
        return $this->yii(['fixture/load', implode(',', $fixtures), '--interactive=0'], 'Load fixtures');
    }

    public function flushCache(): Task
    {
        return $this->yii(['cache/flush-all', '--interactive=0'], 'Flush cache');
    }

    /** @param list<string> $args */
    public function yii(array $args, ?string $name = null): Task
    {
        return $this->cli($this->console, $args, $name ?? "{$this->console} ".implode(' ', $args));
    }

    public function finalize(): array
    {
        return [$this->flushCache()];
    }

    public function requirements(): array
    {
        return [
            'php' => '7.4.0',
            'extensions' => ['ctype', 'mbstring', 'pdo', 'intl'],
            'writable' => ['runtime', 'web/assets', 'config'],
        ];
    }
}
