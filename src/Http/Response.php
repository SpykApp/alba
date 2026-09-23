<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Http;

final class Response
{
    /** @var array<string, string> */
    public array $cookies = [];

    /** @param array<string, string> $headers */
    public function __construct(
        public string $body = '',
        public int $status = 200,
        public array $headers = ['Content-Type' => 'text/html; charset=utf-8'],
    ) {}

    public static function redirect(string $url): self
    {
        return new self('', 302, ['Location' => $url]);
    }

    public static function json(array $data, int $status = 200): self
    {
        return new self(json_encode($data, JSON_UNESCAPED_SLASHES), $status, ['Content-Type' => 'application/json']);
    }

    public function withCookie(string $name, string $value): self
    {
        $this->cookies[$name] = $value;

        return $this;
    }

    public function send(): void
    {
        http_response_code($this->status);
        header('X-Robots-Tag: noindex, nofollow');
        header('Cache-Control: no-store');
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        foreach ($this->cookies as $name => $value) {
            setcookie($name, $value, ['path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
        }
        echo $this->body;
    }
}
