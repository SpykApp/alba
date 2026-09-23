<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Frameworks;

use SpykraLabs\Alba\Support\ConfigFile;
use SpykraLabs\Alba\Support\Context;
use SpykraLabs\Alba\Tasks\{CallbackTask, CommandTask, Task};

/**
 * WordPress. Generates wp-config.php with the database and fresh salts, and can
 * finish the install through WP-CLI (`wp`), which must be available on the server.
 */
final class WordPress extends Framework
{
    public function __construct(private string $tablePrefix = 'wp_', private string $wp = 'wp') {}

    public function name(): string
    {
        return 'wordpress';
    }

    public function detect(string $basePath): bool
    {
        return is_file($basePath.'/wp-load.php') || is_file($basePath.'/wp-config-sample.php');
    }

    /** MySQL/MariaDB only. Writes wp-config.php in the base path. */
    public function writeDatabase(Context $ctx, array $db): void
    {
        if ($db['driver'] !== 'mysql') {
            throw new \RuntimeException('WordPress only supports MySQL/MariaDB.');
        }

        $salts = '';
        foreach (['AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT'] as $name) {
            $salts .= "define( '$name', ".var_export(bin2hex(random_bytes(32)), true)." );\n";
        }

        $host = $db['host'].($db['port'] !== '' && $db['port'] !== '3306' ? ':'.$db['port'] : '');
        $config = "<?php\n"
            ."define( 'DB_NAME', ".var_export($db['database'], true)." );\n"
            ."define( 'DB_USER', ".var_export($db['username'], true)." );\n"
            ."define( 'DB_PASSWORD', ".var_export($db['password'], true)." );\n"
            ."define( 'DB_HOST', ".var_export($host, true)." );\n"
            ."define( 'DB_CHARSET', 'utf8mb4' );\n"
            ."define( 'DB_COLLATE', '' );\n\n"
            .$salts
            ."\n\$table_prefix = ".var_export($this->tablePrefix, true).";\n"
            ."define( 'WP_DEBUG', false );\n\n"
            ."if ( ! defined( 'ABSPATH' ) ) {\n\tdefine( 'ABSPATH', __DIR__ . '/' );\n}\n"
            ."require_once ABSPATH . 'wp-settings.php';\n";

        ConfigFile::write($ctx, 'wp-config.php', $config);
    }

    /** WordPress creates its tables during core install, so migrate is the install itself. */
    public function migrate(): Task
    {
        return $this->coreInstall();
    }

    /**
     * `wp core install`, using answers from a Questions step. Map WP-CLI options
     * to your field names.
     *
     * @param  array<string, string>  $answers  url, title, admin_user, admin_password, admin_email
     */
    public function coreInstall(array $answers = []): Task
    {
        $map = $answers + [
            'url' => 'app_url', 'title' => 'app_name',
            'admin_user' => 'admin_user', 'admin_password' => 'admin_password', 'admin_email' => 'admin_email',
        ];

        return new CallbackTask('Install WordPress', function (Context $ctx) use ($map) {
            $cmd = [$this->wp, 'core', 'install', '--skip-email', '--path='.$ctx->path()];
            foreach ($map as $option => $field) {
                $cmd[] = "--$option=".$ctx->answer($field, '');
            }

            return (new CommandTask($cmd))->run($ctx);
        });
    }

    /** @param list<string> $plugins */
    public function activatePlugins(array $plugins): Task
    {
        return new CommandTask([$this->wp, 'plugin', 'activate', ...$plugins, '--path=.'], 'Activate plugins');
    }

    public function finalize(): array
    {
        return [new CommandTask([$this->wp, 'rewrite', 'flush', '--path=.'], 'Flush permalinks')];
    }

    public function requirements(): array
    {
        return [
            'php' => '7.4.0',
            'extensions' => ['mysqli', 'json', 'mbstring', 'xml', 'curl'],
            'writable' => ['wp-content', 'wp-content/uploads'],
        ];
    }
}
