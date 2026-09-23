<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Http;

final class Request
{
    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $body
     * @param  array<string, string>  $cookies
     * @param  array<string, string>  $headers  lower-cased names
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $query = [],
        public array $body = [],
        public array $cookies = [],
        public array $headers = [],
        public string $ip = '',
    ) {}

    public static function fromGlobals(): self
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = (string) $value;
            }
        }

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH),
            $_GET,
            $_POST,
            $_COOKIE,
            $headers,
            $_SERVER['REMOTE_ADDR'] ?? '',
        );
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }
}
