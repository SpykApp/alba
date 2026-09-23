<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Tasks;

use SpykraLabs\Alba\Files\Guard;
use SpykraLabs\Alba\Support\Context;
use SpykraLabs\Alba\Support\EnvWriter;
use SpykraLabs\Alba\Support\Placeholders;

/** Write KEY=value pairs to an env file. Values may use {{ placeholders }}. */
final class WriteEnvTask implements Task
{
    /**
     * @param  array<string, string>  $values
     * @param  bool  $keepExisting  do not overwrite keys that already have a value
     */
    public function __construct(
        private array $values,
        private ?string $file = null,
        private string $name = 'Write environment file',
        private bool $keepExisting = false,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function run(Context $ctx): string
    {
        $relative = $this->file ?? $ctx->alba->envFile ?? '.env';
        $writer = new EnvWriter(Guard::inside($ctx, $relative));
        $write = [];

        foreach ($this->values as $key => $value) {
            if ($this->keepExisting && ($writer->get($key) ?? '') !== '') {
                continue;
            }
            $write[$key] = Placeholders::resolve($value, $ctx);
        }
        $writer->set($write);

        return 'wrote '.implode(', ', array_keys($write) ?: ['nothing new']).' to '.$relative;
    }
}
