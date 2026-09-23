<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Steps;

final class StepResult
{
    /** @param array<string, string> $errors */
    private function __construct(
        public bool $ok,
        public array $errors = [],
        public ?string $message = null,
    ) {}

    public static function ok(?string $message = null): self
    {
        return new self(true, [], $message);
    }

    /** @param array<string, string>|string $errors */
    public static function fail(array|string $errors): self
    {
        return new self(false, is_string($errors) ? ['_' => $errors] : $errors);
    }
}
