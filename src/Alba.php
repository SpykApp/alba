<?php

declare(strict_types=1);

namespace SpykraLabs\Alba;

use Closure;
use SpykraLabs\Alba\Frameworks\{Framework, Frameworks};
use SpykraLabs\Alba\Http\Kernel;
use SpykraLabs\Alba\Installed\InstalledBehavior;
use SpykraLabs\Alba\Http\Request;
use SpykraLabs\Alba\Http\Response;
use SpykraLabs\Alba\Steps\StepInterface;
use SpykraLabs\Alba\Themes\Theme;

/**
 * Alba - PHP App Installer. Fluent configuration + entry point.
 */
final class Alba
{
    public string $route = '/install';

    public string $basePath;

    public ?string $envFile = '.env';

    public string $storagePath;

    public string $redirectTo = '/';

    public ?string $extraCss = null;

    public ?Theme $themePack = null;

    public InstalledBehavior $whenInstalled;

    private Framework|string|null $framework = null;

    /** @var array{text: string, url: ?string}|false */
    public array|false $poweredBy = ['text' => 'Alba · PHP App Installer by SpykraLabs', 'url' => null];

    /** @var array<string, string> */
    public array $theme = [];

    /** @var list<StepInterface> */
    public array $steps = [];

    /** @var list<string> */
    public array $viewPaths = [];

    public ?Closure $guard = null;

    private function __construct(public string $name)
    {
        $this->basePath = getcwd() ?: '.';
        $this->storagePath = $this->basePath.'/storage/alba';
        $this->whenInstalled = InstalledBehavior::page();
    }

    public static function configure(string $name = 'Application'): self
    {
        return new self($name);
    }

    public function route(string $route): self
    {
        $this->route = '/'.trim($route, '/');

        return $this;
    }

    public function basePath(string $path): self
    {
        $this->basePath = rtrim($path, '/\\');
        $this->storagePath = $this->basePath.'/storage/alba';

        return $this;
    }

    /** Pass null to skip writing an env file. */
    public function envFile(?string $file): self
    {
        $this->envFile = $file;

        return $this;
    }

    public function storagePath(string $path): self
    {
        $this->storagePath = rtrim($path, '/\\');

        return $this;
    }

    public function redirectTo(string $url): self
    {
        $this->redirectTo = $url;

        return $this;
    }

    /**
     * Either a Theme pack, or an array of overrides:
     * title, logo (string | ['light' => url, 'dark' => url]), brand, radius, font.
     * Array values win over the pack, so a pack can be tweaked per project.
     *
     * @param  array<string, mixed>|Theme  $theme
     */
    public function theme(array|Theme $theme): self
    {
        if ($theme instanceof Theme) {
            $this->themePack = $theme;
            if ($theme->viewsPath()) {
                $this->viewPaths[] = rtrim($theme->viewsPath(), '/\\');
            }

            return $this;
        }
        $this->theme = $theme + $this->theme;

        return $this;
    }

    /** The footer line. Pass false to hide it. Text is escaped; url is optional. */
    public function poweredBy(string|false $text, ?string $url = null): self
    {
        $this->poweredBy = $text === false ? false : ['text' => $text, 'url' => $url];

        return $this;
    }

    /**
     * Tell Alba which framework the app uses, so the Database step writes the
     * framework's own configuration. Pass a Framework object, a name
     * ("laravel", "symfony", ...) or "auto" to detect it from the app files.
     */
    public function framework(Framework|string|null $framework): self
    {
        $this->framework = $framework;

        return $this;
    }

    public function frameworkPreset(): ?Framework
    {
        return match (true) {
            $this->framework instanceof Framework => $this->framework,
            $this->framework === 'auto' => Frameworks::detect($this->basePath),
            is_string($this->framework) => Frameworks::named($this->framework),
            default => null,
        };
    }

    /** What visitors see at the installer route after installation. */
    public function whenInstalled(InstalledBehavior $behavior): self
    {
        $this->whenInstalled = $behavior;

        return $this;
    }

    public function extraCss(string $path): self
    {
        $this->extraCss = $path;

        return $this;
    }

    /** Directories searched (first) for template overrides. */
    public function viewsPath(string $path): self
    {
        array_unshift($this->viewPaths, rtrim($path, '/\\'));

        return $this;
    }

    /** Return false from the callback to block access to the installer. */
    public function guard(Closure $guard): self
    {
        $this->guard = $guard;

        return $this;
    }

    /** @param list<StepInterface> $steps */
    public function steps(array $steps): self
    {
        $this->steps = array_values($steps);

        return $this;
    }

    public function handle(Request $request): Response
    {
        return (new Kernel($this))->handle($request);
    }

    /** Plain PHP entry point: read globals, emit the response. */
    public function run(): void
    {
        $this->handle(Request::fromGlobals())->send();
    }
}
