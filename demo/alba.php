<?php

use SpykraLabs\Alba\Alba;
use SpykraLabs\Alba\Files\{Copy, Delete};
use SpykraLabs\Alba\License\LicenseResult;
use SpykraLabs\Alba\Steps\{Database, Finish, License, Permissions, Questions, Requirements, TaskStep, Welcome};
use SpykraLabs\Alba\Tasks\{CallbackTask, CommandTask, SqlDirectoryTask};

return Alba::configure('Demo App')
    ->route('/install')
    ->basePath(__DIR__)
    ->envFile('.env')
    ->redirectTo('/')
    ->theme(['title' => 'Demo App'])
    // Run with ALBA_THEME=ocean to load the sample theme pack (tokens + css + assets):
    ->theme(getenv('ALBA_THEME') === 'ocean' ? \SpykraLabs\Alba\Themes\DirectoryTheme::from(__DIR__.'/themes/ocean') : [])
    ->whenInstalled(match (getenv('ALBA_INSTALLED')) {
        'status' => \SpykraLabs\Alba\Installed\InstalledBehavior::status(404),
        'redirect' => \SpykraLabs\Alba\Installed\InstalledBehavior::redirect('/'),
        'wizard' => \SpykraLabs\Alba\Installed\InstalledBehavior::wizard(),
        default => \SpykraLabs\Alba\Installed\InstalledBehavior::page(),
    })
    ->locale('auto')          // follow the visitor's browser language
    ->languageSwitcher()      // show a language picker in the sidebar
    ->poweredBy('Powered by Demo App Inc.', 'https://example.com')
    ->steps([
        Welcome::make()->withHeading('Welcome to Demo App', 'Setup takes about two minutes.')
            ->withInstructions('<strong>Before you start:</strong> have your database details and licence key handy.'),

        Requirements::make()
            ->php('8.1.0')
            ->extensions(['pdo', 'pdo_sqlite', 'mbstring', 'json', 'openssl'])
            ->iniAtLeast('memory_limit', '64M')
            ->functions(['proc_open']),

        Permissions::make()->writable(['storage', '.env', 'app']),

        Database::make()
            ->withInstructions('Use SQLite to try the demo, or point to MySQL/PostgreSQL. Credentials are saved to <code>.env</code>.')
            ->drivers(['sqlite', 'mysql', 'pgsql'])->sqliteDefault('storage/app.sqlite'),

        // Custom licensing: ALBA-PRO-0001 => pro, ALBA-LITE-0001 => lite.
        License::make()
            ->codeLabel('License key')
            ->verifier(fn (string $code) => match (strtoupper($code)) {
                'ALBA-PRO-0001' => LicenseResult::valid('pro'),
                'ALBA-LITE-0001' => LicenseResult::valid('lite'),
                default => LicenseResult::invalid('Unknown licence key. Try ALBA-PRO-0001 or ALBA-LITE-0001.'),
            })
            ->onType('*', [Delete::path('app/Legacy')])
            ->onType('pro', [Copy::from('editions/pro', 'app/Edition')])
            ->onType('lite', [Copy::from('editions/lite', 'app/Edition')]),

        Questions::make()
            ->field('app_name', 'Application name', rules: 'required|max:60', env: 'APP_NAME', default: 'Demo App')
            ->field('app_url', 'Application URL', type: 'url', rules: 'required|url', env: 'APP_URL', default: 'http://localhost:8088')
            ->field('timezone', 'Timezone', type: 'select', env: 'APP_TIMEZONE', options: ['UTC' => 'UTC', 'Europe/London' => 'London', 'Asia/Kolkata' => 'Kolkata'])
            ->field('admin_name', 'Admin name', rules: 'required')
            ->field('admin_email', 'Admin email', type: 'email', rules: 'required|email')
            ->field('admin_password', 'Admin password', type: 'password', rules: 'required|min:8', help: 'At least 8 characters.'),

        TaskStep::migrate()->add(new SqlDirectoryTask('database/migrations', 'Create tables')),

        TaskStep::seed()->add(
            new SqlDirectoryTask('database/seeds', 'Insert default settings', 'alba_seeds'),
            new CallbackTask('Create admin account', function ($ctx) {
                $ctx->pdo()->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)')->execute([
                    $ctx->answer('admin_name'),
                    $ctx->answer('admin_email'),
                    password_hash($ctx->answer('admin_password'), PASSWORD_DEFAULT),
                ]);

                return 'admin '.$ctx->answer('admin_email').' created';
            }),
        ),

        TaskStep::commands()->add(
            new CommandTask([PHP_BINARY, '-r', 'echo "Cache warmed (" . PHP_VERSION . ")";'], 'Warm cache'),
            new CallbackTask('Write install marker', function ($ctx) {
                file_put_contents($ctx->path('storage/installed_at.txt'), date(DATE_ATOM));

                return 'storage/installed_at.txt written';
            }),
        ),

        Finish::make(),
    ]);
