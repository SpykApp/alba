<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\License;

final class LicenseResult
{
    /** @param array<string, mixed> $meta */
    private function __construct(
        public bool $valid,
        public string $type = 'standard',
        public ?string $message = null,
        public array $meta = [],
    ) {}

    /** @param array<string, mixed> $meta */
    public static function valid(string $type = 'standard', array $meta = []): self
    {
        return new self(true, $type, null, $meta);
    }

    public static function invalid(string $message): self
    {
        return new self(false, 'none', $message);
    }
}
