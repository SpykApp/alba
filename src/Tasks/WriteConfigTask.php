<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Tasks;

use SpykraLabs\Alba\Support\ConfigFile;
use SpykraLabs\Alba\Support\Context;
use SpykraLabs\Alba\Support\Placeholders;

/**
 * Write a config file from a template with {{ placeholders }}.
 * Pass the template text, or a path to a template file (relative to the base path).
 */
final class WriteConfigTask implements Task
{
    public function __construct(
        private string $path,
        private string $template,
        private ?string $name = null,
        private bool $overwrite = true,
    ) {}

    public function name(): string
    {
        return $this->name ?? "Write {$this->path}";
    }

    public function run(Context $ctx): string
    {
        if (! $this->overwrite && is_file($ctx->path($this->path))) {
            return "kept existing {$this->path}";
        }

        $template = ! str_contains($this->template, "\n") && is_file($ctx->path($this->template))
            ? (string) file_get_contents($ctx->path($this->template))
            : $this->template;

        ConfigFile::write($ctx, $this->path, Placeholders::resolve($template, $ctx));

        return "wrote {$this->path}";
    }
}
