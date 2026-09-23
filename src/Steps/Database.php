<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Steps;

use PDOException;
use SpykraLabs\Alba\Http\Request;
use SpykraLabs\Alba\Support\Context;
use SpykraLabs\Alba\Support\Database as Connector;
use SpykraLabs\Alba\Support\EnvWriter;
use SpykraLabs\Alba\Support\Validator;

final class Database extends AbstractStep
{
    protected string $key = 'database';

    protected string $title = 'Database';

    protected string $description = 'Connect the application to its database.';

    /** @var list<string> */
    private array $drivers = ['mysql', 'pgsql', 'sqlite'];

    private string $sqliteDefault = 'storage/database.sqlite';

    /** @param list<string> $drivers mysql, pgsql, sqlite */
    public function drivers(array $drivers): self
    {
        $this->drivers = $drivers;

        return $this;
    }

    public function sqliteDefault(string $path): self
    {
        $this->sqliteDefault = $path;

        return $this;
    }

    public function viewData(Context $ctx): array
    {
        return [
            'drivers' => $this->drivers,
            'saved' => $ctx->state->get('db', []),
            'sqliteDefault' => $this->sqliteDefault,
        ];
    }

    public function handle(Request $request, Context $ctx): StepResult
    {
        $driver = (string) $request->input('driver');
        if (! in_array($driver, $this->drivers, true)) {
            return StepResult::fail(['driver' => 'Choose a supported database driver.']);
        }

        $isSqlite = $driver === 'sqlite';
        $errors = Validator::validate([
            'database' => ['label' => $isSqlite ? 'Database file' : 'Database name', 'rules' => 'required'],
            ...($isSqlite ? [] : [
                'host' => ['label' => 'Host', 'rules' => 'required'],
                'port' => ['label' => 'Port', 'rules' => 'required|numeric'],
                'username' => ['label' => 'Username', 'rules' => 'required'],
            ]),
        ], $request->body);
        if ($errors) {
            return StepResult::fail($errors);
        }

        $db = [
            'driver' => $driver,
            'host' => trim((string) $request->input('host')),
            'port' => trim((string) $request->input('port')),
            'database' => trim((string) $request->input('database')),
            'username' => trim((string) $request->input('username')),
            'password' => (string) $request->input('password', ''),
        ];

        try {
            Connector::connect($db, $ctx);
        } catch (PDOException $e) {
            return StepResult::fail('Could not connect: '.$e->getMessage());
        }

        $ctx->state->put('db', $db);

        if ($framework = $ctx->alba->frameworkPreset()) {
            try {
                $framework->writeDatabase($ctx, $db);
            } catch (\Throwable $e) {
                return StepResult::fail('Connected, but could not write the configuration: '.$e->getMessage());
            }
        } elseif ($ctx->alba->envFile !== null) {
            (new EnvWriter($ctx->path($ctx->alba->envFile)))->set([
                'DB_CONNECTION' => $driver,
                'DB_HOST' => $db['host'],
                'DB_PORT' => $db['port'],
                'DB_DATABASE' => $isSqlite ? Connector::sqlitePath($db['database'], $ctx) : $db['database'],
                'DB_USERNAME' => $db['username'],
                'DB_PASSWORD' => $db['password'],
            ]);
        }

        return StepResult::ok('Database connection verified.');
    }
}
