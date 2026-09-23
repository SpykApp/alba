<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Support;

use RuntimeException;
use SpykraLabs\Alba\Files\Guard;

/** Writes config files inside the application, creating folders as needed. */
final class ConfigFile
{
    public static function write(Context $ctx, string $relative, string $contents): string
    {
        $path = Guard::inside($ctx, $relative);
        if (! is_dir(dirname($path)) && ! @mkdir(dirname($path), 0775, true)) {
            throw new RuntimeException("Cannot create the folder for [$relative].");
        }
        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new RuntimeException("Cannot write [$relative]. Check its permissions.");
        }

        return $path;
    }

    /** Write `<?php return [...];` */
    public static function writePhpArray(Context $ctx, string $relative, array $data): string
    {
        return self::write($ctx, $relative, "<?php\n\nreturn ".self::export($data).";\n");
    }

    public static function export(mixed $value, int $depth = 0): string
    {
        if (! is_array($value)) {
            return var_export($value, true);
        }
        if ($value === []) {
            return '[]';
        }
        $pad = str_repeat('    ', $depth + 1);
        $isList = array_is_list($value);
        $lines = [];
        foreach ($value as $k => $v) {
            $lines[] = $pad.($isList ? '' : var_export($k, true).' => ').self::export($v, $depth + 1).',';
        }

        return "[\n".implode("\n", $lines)."\n".str_repeat('    ', $depth).']';
    }
}
