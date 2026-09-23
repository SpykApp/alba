<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Tasks;

use RuntimeException;
use SpykraLabs\Alba\Support\Context;

/** Runs every *.sql file in a directory in name order, remembering what already ran. */
final class SqlDirectoryTask implements Task
{
    public function __construct(
        private string $directory,
        private string $name = 'Run SQL files',
        private string $trackingTable = 'alba_migrations',
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function run(Context $ctx): string
    {
        $dir = $ctx->path($this->directory);
        $files = glob($dir.'/*.sql') ?: [];
        sort($files);
        if (! $files) {
            throw new RuntimeException("No .sql files found in $this->directory.");
        }

        $pdo = $ctx->pdo();
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$this->trackingTable} (name VARCHAR(255) PRIMARY KEY)");
        $ran = $pdo->query("SELECT name FROM {$this->trackingTable}")->fetchAll(\PDO::FETCH_COLUMN);

        $log = [];
        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $ran, true)) {
                $log[] = "skipped $name";

                continue;
            }
            $pdo->exec((string) file_get_contents($file));
            $pdo->prepare("INSERT INTO {$this->trackingTable} (name) VALUES (?)")->execute([$name]);
            $log[] = "ran $name";
        }

        return implode("\n", $log);
    }
}
