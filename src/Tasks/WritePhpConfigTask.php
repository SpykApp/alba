<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Tasks;

use SpykraLabs\Alba\Support\ConfigFile;
use SpykraLabs\Alba\Support\Context;
use SpykraLabs\Alba\Support\Placeholders;

/** Write a PHP file that returns an array. String values may use {{ placeholders }}. */
final class WritePhpConfigTask implements Task
{
    /** @param array<string, mixed> $config */
    public function __construct(private string $path, private array $config, private ?string $name = null) {}

    public function name(): string
    {
        return $this->name ?? "Write {$this->path}";
    }

    public function run(Context $ctx): string
    {
        ConfigFile::writePhpArray($ctx, $this->path, Placeholders::resolveArray($this->config, $ctx));

        return "wrote {$this->path}";
    }
}
