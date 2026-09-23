<?php

/*
 * Alba installer for this Laravel app. LaravelServiceProvider (auto-discovered)
 * loads this file and mounts the installer at the route below.
 *
 * Try it: start the app, open /install. Licence keys for the demo:
 *   ALBA-PRO-0001 (pro)   ALBA-LITE-0001 (lite)
 * Start over with: ./reset.sh
 */

use SpykraLabs\Alba\Alba;
use SpykraLabs\Alba\Files\Copy;
use SpykraLabs\Alba\Frameworks\Laravel;
use SpykraLabs\Alba\Installed\InstalledBehavior;
use SpykraLabs\Alba\License\LicenseResult;
use SpykraLabs\Alba\Steps\{Database, Finish, License, Permissions, Questions, Requirements, TaskStep, Welcome};
use SpykraLabs\Alba\Support\Context;
use SpykraLabs\Alba\Tasks\{CallbackTask, WriteEnvTask};

$laravel = Laravel::make();

return Alba::configure('Laravel Demo')
    ->route('/install')
    ->framework($laravel)
    ->redirectTo('/')
    ->whenInstalled(InstalledBehavior::status(404))
    ->locale('auto')          // follow the visitor's browser language
    ->languageSwitcher()      // show a language picker in the sidebar
    ->poweredBy('Powered by Alba', 'https://github.com/spykralabs')
    ->steps([
        Welcome::make()
            // Your own texts can be a string, or one string per language:
            ->withHeading(
                ['en' => 'Install Laravel Demo', 'es' => 'Instalar Laravel Demo', 'fr' => 'Installer Laravel Demo'],
                ['en' => 'A Laravel app installed with Alba.', 'es' => 'Una app Laravel instalada con Alba.', 'fr' => 'Une application Laravel installée avec Alba.'],
            )
            ->withInstructions('This wizard checks the server, writes <code>.env</code>, runs <code>artisan migrate</code> and <code>db:seed</code>, and finishes with Laravel\'s own commands.'),

        Requirements::make()->forFramework($laravel)->functions(['proc_open']),

        Permissions::make()->forFramework($laravel),

        Database::make()
            ->drivers(['sqlite', 'mysql', 'pgsql'])
            ->sqliteDefault('database/database.sqlite')
            ->withInstructions('Alba writes the <code>DB_*</code> keys to <code>.env</code> after testing the connection.'),

        License::make()
            ->codeLabel('License key')
            ->verifier(fn (string $code) => match (strtoupper($code)) {
                'ALBA-PRO-0001' => LicenseResult::valid('pro'),
                'ALBA-LITE-0001' => LicenseResult::valid('lite'),
                default => LicenseResult::invalid('Unknown key. Try ALBA-PRO-0001 or ALBA-LITE-0001.'),
            })
            ->onType('pro', [Copy::from('editions/pro', 'storage/app/edition')])
            ->onType('lite', [Copy::from('editions/lite', 'storage/app/edition')]),

        Questions::make()
            ->withTitle('Settings', 'Tell us about your site.')
            ->field('app_name', 'Application name', rules: 'required|max:60', env: 'APP_NAME', default: 'Laravel Demo')
            ->field('app_url', 'Application URL', type: 'url', rules: 'required|url', env: 'APP_URL', default: 'http://localhost:8000')
            ->field('admin_name', 'Admin name', rules: 'required')
            ->field('admin_email', 'Admin email', type: 'email', rules: 'required|email')
            ->field('admin_password', 'Admin password', type: 'password', rules: 'required|min:8'),

        TaskStep::migrate()->using($laravel),

        TaskStep::seed()
            ->using($laravel)   // php artisan db:seed --force
            ->add(new CallbackTask('Create admin account', function (Context $ctx) {
                $ctx->pdo()->prepare('INSERT INTO users (name, email, password, created_at, updated_at) VALUES (?, ?, ?, ?, ?)')->execute([
                    $ctx->answer('admin_name'),
                    $ctx->answer('admin_email'),
                    password_hash($ctx->answer('admin_password'), PASSWORD_BCRYPT),
                    $now = date('Y-m-d H:i:s'),
                    $now,
                ]);

                return 'admin '.$ctx->answer('admin_email').' created';
            })),

        TaskStep::commands()
            ->using($laravel)   // APP_KEY, storage:link, optimize:clear
            ->add(new WriteEnvTask(['APP_EDITION' => '{{ license.type }}'], name: 'Record edition in .env')),

        Finish::make()->message('Laravel Demo is installed. Sign in with the admin account you created.'),
    ]);
