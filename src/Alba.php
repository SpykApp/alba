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

    /** @var array{text: string|array<string, string>|null, url: ?string}|false  text null = translated default credit */
    public array|false $poweredBy = ['text' => null, 'url' => null];

    /** 'auto' detects the visitor's language; otherwise a fixed locale such as 'es'. */
    public string $locale = 'en';

    public string $fallbackLocale = 'en';

    public ?string $langPath = null;

    /** @var array<string, array<string, string>> */
    public array $translations = [];

    /** @var list<string>|null allowed locales, null = every available one */
    public ?array $languages = null;

    public bool $languageSwitcher = false;

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

    /** Default language: a code like 'es' or 'pt-BR', or 'auto' to follow the visitor's browser. */
    public function locale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    /** Used for any string missing in the active language (default 'en'). */
    public function fallbackLocale(string $locale): self
    {
        $this->fallbackLocale = $locale;

        return $this;
    }

    /** A folder of `{locale}.php` files that add languages or override built-in strings. */
    public function langPath(string $path): self
    {
        $this->langPath = rtrim($path, '/\\');

        return $this;
    }

    /**
     * Add or override strings in code: ['es' => ['ui.continue' => 'Seguir']].
     *
     * @param  array<string, array<string, string>>  $translations
     */
    public function translations(array $translations): self
    {
        foreach ($translations as $locale => $lines) {
            $this->translations[$locale] = $lines + ($this->translations[$locale] ?? []);
        }

        return $this;
    }

    /** Limit which languages visitors can pick or be detected. @param list<string> $locales */
    public function languages(array $locales): self
    {
        $this->languages = $locales;

        return $this;
    }

    /** Show a language switcher in the sidebar. */
    public function languageSwitcher(bool $show = true): self
    {
        $this->languageSwitcher = $show;

        return $this;
    }

    /**
     * The footer line. Pass false to hide it. Text is escaped and may be a string or
     * a locale keyed array; url is optional.
     *
     * @param  string|array<string, string>|false  $text
     */
    public function poweredBy(string|array|false $text, ?string $url = null): self
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
