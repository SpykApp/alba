<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Support;

use RuntimeException;
use SpykraLabs\Alba\Alba;

final class View
{
    public function __construct(private Alba $alba) {}

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = []): string
    {
        return $this->include($template, $data);
    }

    /** @param array<string, mixed> $data */
    public function page(string $template, array $data): string
    {
        $data['content'] = $this->include($template, $data);

        return $this->include('layout', $data);
    }

    /** @param array<string, mixed> $data */
    private function include(string $template, array $data): string
    {
        $file = null;
        foreach ([...$this->alba->viewPaths, dirname(__DIR__, 2).'/resources/views'] as $dir) {
            if (is_file("$dir/$template.php")) {
                $file = "$dir/$template.php";
                break;
            }
        }
        $file ?? throw new RuntimeException("Alba view [$template] not found.");

        $e = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $data['e'] = $e;
        $data['view'] = $this;

        return (static function (string $__file, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();
            include $__file;

            return (string) ob_get_clean();
        })($file, $data);
    }
}
