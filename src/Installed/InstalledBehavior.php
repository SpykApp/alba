<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Installed;

/**
 * What visitors get at the installer route once the app is installed.
 * Pick one with the static constructors; the default is page().
 */
final class InstalledBehavior
{
    public const PAGE = 'page';

    public const STATUS = 'status';

    public const REDIRECT = 'redirect';

    public const WIZARD = 'wizard';

    /** @param array<string, mixed> $options */
    private function __construct(public string $mode, public array $options = []) {}

    /**
     * Show a themed "already installed" page (the default).
     *
     * @param  ?string  $buttonUrl  where the button goes; defaults to Alba::redirectTo. Pass '' to hide the button.
     */
    public static function page(
        string $title = 'Already installed',
        string $message = 'This application has been installed and the installer is locked.',
        string $buttonLabel = 'Open the app',
        ?string $buttonUrl = null,
        int $status = 200,
    ): self {
        return new self(self::PAGE, compact('title', 'message', 'buttonLabel', 'buttonUrl', 'status'));
    }

    /** Answer with a bare HTTP status code (404 hides the installer completely). */
    public static function status(int $code = 404, ?string $body = null): self
    {
        return new self(self::STATUS, ['code' => $code, 'body' => $body]);
    }

    /** Send visitors somewhere else, e.g. the home page. */
    public static function redirect(string $url = '/', int $code = 302): self
    {
        return new self(self::REDIRECT, compact('url', 'code'));
    }

    /**
     * Keep serving the wizard after install (for apps that offer re-running or
     * re-configuring). Anyone who can reach the route can change your setup,
     * so combine it with Alba::guard().
     */
    public static function wizard(): self
    {
        return new self(self::WIZARD);
    }
}
