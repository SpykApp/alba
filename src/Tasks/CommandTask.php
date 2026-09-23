<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Tasks;

use RuntimeException;
use SpykraLabs\Alba\Support\Context;

/** Runs a shell command (argv array, no shell interpolation) in the app base path. */
final class CommandTask implements Task
{
    /** @param list<string> $command */
    public function __construct(private array $command, private ?string $name = null) {}

    public function name(): string
    {
        return $this->name ?? implode(' ', $this->command);
    }

    public function run(Context $ctx): string
    {
        $process = proc_open($this->command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $ctx->path());
        if (! is_resource($process)) {
            throw new RuntimeException('Could not start the command.');
        }

        $out = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
        $code = proc_close($process);

        if ($code !== 0) {
            throw new RuntimeException(trim($out) ?: "Command exited with code $code.");
        }

        return trim($out);
    }
}
