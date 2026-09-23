<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Frameworks;

use SpykraLabs\Alba\Support\Context;
use SpykraLabs\Alba\Support\Database;
use SpykraLabs\Alba\Tasks\Task;

/** Standalone doctrine/migrations (Laminas, Slim, Mezzio, plain PHP). Reads DATABASE_URL from your env file. */
final class DoctrineMigrations extends Framework
{
    public function name(): string
    {
        return 'doctrine';
    }

    public function detect(string $basePath): bool
    {
        return is_file($basePath.'/vendor/bin/doctrine-migrations');
    }

    public function writeDatabase(Context $ctx, array $db): void
    {
        $this->writeEnv($ctx, ['DATABASE_URL' => Database::url($db, $ctx)]);
    }

    public function migrate(): Task
    {
        return $this->cli('vendor/bin/doctrine-migrations', ['migrate', '--no-interaction', '--allow-no-migration'], 'Run migrations');
    }

    /** ORM users without migrations can create the schema directly (needs doctrine/orm's CLI). */
    public function createSchema(): Task
    {
        return $this->cli('vendor/bin/doctrine', ['orm:schema-tool:create'], 'Create database schema');
    }

    public function requirements(): array
    {
        return ['php' => '8.1.0', 'extensions' => ['pdo'], 'writable' => ['.env']];
    }
}
