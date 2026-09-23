<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Http;

use SpykraLabs\Alba\Alba;
use SpykraLabs\Alba\Installed\InstalledBehavior;
use SpykraLabs\Alba\Steps\Finish;
use SpykraLabs\Alba\Steps\StepInterface;
use SpykraLabs\Alba\Steps\StepResult;
use SpykraLabs\Alba\Steps\TaskStep;
use SpykraLabs\Alba\Support\Context;
use SpykraLabs\Alba\Support\Lang;
use SpykraLabs\Alba\Support\StateStore;
use SpykraLabs\Alba\Support\Translator;
use SpykraLabs\Alba\Support\View;

/** Framework-agnostic request router for the installer wizard. */
final class Kernel
{
    private Context $ctx;

    private View $view;

    /** @var array<string, StepInterface> */
    private array $steps = [];

    private string $token = '';

    private bool $newToken = false;

    private Translator $translator;

    private ?string $localeCookie = null;

    public function __construct(private Alba $alba)
    {
        $this->ctx = new Context($alba, new StateStore($alba->storagePath));
        $this->view = new View($alba);
        foreach ($alba->steps as $step) {
            $this->steps[$step->key()] = $step;
        }
    }

    public function handle(Request $request): Response
    {
        $path = '/'.trim($request->path, '/');
        $base = rtrim($this->alba->route, '/');
        $this->bootLocale($request);

        if ($base !== '' && $path !== $base && ! str_starts_with($path, $base.'/')) {
            return new Response(Lang::t('ui.not_found'), 404, ['Content-Type' => 'text/plain']);
        }
        $rest = trim(substr($path, strlen($base)), '/');

        $this->token = $request->cookies['alba_csrf'] ?? '';
        if (strlen($this->token) < 32) {
            $this->token = bin2hex(random_bytes(24));
            $this->newToken = true;
        }

        $response = $this->route($request, $rest);
        if ($this->newToken) {
            $response->withCookie('alba_csrf', $this->token);
        }
        if ($this->localeCookie) {
            $response->withCookie('alba_locale', $this->localeCookie);
        }

        return $response;
    }

    /** Pick the language: ?lang= (remembered), cookie, browser (when locale is 'auto'), configured default. */
    private function bootLocale(Request $request): void
    {
        $available = array_keys((new Translator($this->alba, $this->alba->fallbackLocale))->available());
        $chosen = null;

        $query = $request->query['lang'] ?? null;
        if (is_string($query) && in_array($query, $available, true)) {
            $chosen = $this->localeCookie = $query;
        }
        $cookie = $request->cookies['alba_locale'] ?? null;
        if (! $chosen && is_string($cookie) && in_array($cookie, $available, true)) {
            $chosen = $cookie;
        }
        if (! $chosen && $this->alba->locale === 'auto') {
            $chosen = $this->fromAcceptLanguage($request->headers['accept-language'] ?? '', $available);
        }
        if (! $chosen && $this->alba->locale !== 'auto' && in_array($this->alba->locale, $available, true)) {
            $chosen = $this->alba->locale;
        }
        $chosen ??= in_array($this->alba->fallbackLocale, $available, true) ? $this->alba->fallbackLocale : ($available[0] ?? 'en');

        $this->translator = new Translator($this->alba, $chosen);
        Lang::use($this->translator);
    }

    /** @param list<string> $available */
    private function fromAcceptLanguage(string $header, array $available): ?string
    {
        $lookup = array_combine(array_map('strtolower', $available), $available);
        $wanted = [];
        foreach (explode(',', $header) as $part) {
            [$tag, $q] = array_pad(explode(';q=', trim($part), 2), 2, '1');
            if ($tag !== '' && $tag !== '*') {
                $wanted[strtolower(trim($tag))] = (float) $q;
            }
        }
        arsort($wanted);
        foreach (array_keys($wanted) as $tag) {
            foreach ([$tag, explode('-', $tag)[0]] as $try) {
                if (isset($lookup[$try])) {
                    return $lookup[$try];
                }
            }
        }

        return null;
    }

    private function route(Request $request, string $rest): Response
    {
        if (str_starts_with($rest, '_alba/')) {
            return $this->asset(substr($rest, 6));
        }

        if ($this->alba->guard && ($this->alba->guard)($request) === false) {
            return new Response($this->view->render('blocked', []), 403);
        }

        if ($this->ctx->state->isInstalled() && $this->alba->whenInstalled->mode !== InstalledBehavior::WIZARD) {
            return $this->installed($this->alba->whenInstalled);
        }

        $keys = array_keys($this->steps);
        $current = $this->currentIndex($keys);
        $segments = $rest === '' ? [] : explode('/', $rest);

        if (! $segments) {
            return Response::redirect($this->ctx->url($keys[$current]));
        }

        $step = $this->steps[$segments[0]] ?? null;
        if (! $step) {
            return new Response(Lang::t('ui.not_found'), 404, ['Content-Type' => 'text/plain']);
        }
        if (array_search($step->key(), $keys, true) > $current) {
            return Response::redirect($this->ctx->url($keys[$current]));
        }

        if (($segments[1] ?? null) === 'run' && $request->method === 'POST' && $step instanceof TaskStep) {
            return $this->runTask($request, $step, (int) ($segments[2] ?? -1));
        }

        if ($request->method === 'POST') {
            return $this->submit($request, $step, $keys);
        }

        return $this->show($step);
    }

    private function installed(InstalledBehavior $behavior): Response
    {
        $o = $behavior->options;

        return match ($behavior->mode) {
            InstalledBehavior::STATUS => new Response((string) ($o['body'] ?? ''), $o['code'], ['Content-Type' => 'text/plain; charset=utf-8']),
            InstalledBehavior::REDIRECT => new Response('', $o['code'], ['Location' => $o['url']]),
            default => new Response($this->view->page('installed', $this->layoutData(null) + $o), $o['status']),
        };
    }

    private function show(StepInterface $step, ?StepResult $result = null, array $old = []): Response
    {
        $data = $this->layoutData($step) + [
            'step' => $step,
            'errors' => $result?->errors ?? [],
            'old' => $old,
            'notice' => $this->ctx->state->pull('notice'),
        ] + $step->viewData($this->ctx);

        return new Response($this->view->page($step->view(), $data), $result && ! $result->ok ? 422 : 200);
    }

    /** @param list<string> $keys */
    private function submit(Request $request, StepInterface $step, array $keys): Response
    {
        if (! hash_equals($this->token, (string) $request->input('_token', ''))) {
            return new Response(Lang::t('ui.page_expired'), 419, ['Content-Type' => 'text/plain']);
        }

        $result = $step->handle($request, $this->ctx);
        if (! $result->ok) {
            return $this->show($step, $result, $request->body);
        }

        $this->ctx->state->markDone($step->key());
        if ($result->message) {
            $this->ctx->state->put('notice', $result->message);
        }

        if ($step instanceof Finish) {
            $this->ctx->state->clear();
            $this->ctx->state->lock();

            return Response::redirect($this->alba->redirectTo);
        }

        $next = $keys[array_search($step->key(), $keys, true) + 1] ?? $step->key();

        return Response::redirect($this->ctx->url($next));
    }

    private function runTask(Request $request, TaskStep $step, int $index): Response
    {
        $sent = $request->headers['x-csrf-token'] ?? (string) $request->input('_token', '');
        if (! hash_equals($this->token, $sent)) {
            return Response::json(['ok' => false, 'log' => Lang::t('ui.page_expired')], 419);
        }

        // Tasks run strictly in order: every earlier task must have succeeded.
        $results = $this->ctx->state->get('tasks', [])[$step->key()] ?? [];
        for ($i = 0; $i < $index; $i++) {
            if (! ($results[$i]['ok'] ?? false)) {
                return Response::json(['ok' => false, 'log' => Lang::t('ui.earlier_incomplete')], 409);
            }
        }
        if (! isset($step->tasks()[$index])) {
            return Response::json(['ok' => false, 'log' => Lang::t('ui.unknown_task')], 404);
        }

        return Response::json($step->runTask($index, $this->ctx));
    }

    private function asset(string $file): Response
    {
        $types = ['alba.css' => 'text/css', 'alba.js' => 'application/javascript'];
        $css = match ($file) {
            'theme.css' => $this->alba->themePack?->cssFile(),
            'custom.css' => $this->alba->extraCss,
            default => null,
        };
        if ($css && is_file($css)) {
            return new Response((string) file_get_contents($css), 200, ['Content-Type' => 'text/css; charset=utf-8']);
        }
        if (str_starts_with($file, 'theme/') && $this->alba->themePack?->assetsPath()) {
            $root = realpath($this->alba->themePack->assetsPath());
            $real = realpath($root.'/'.substr($file, 6));
            if ($root && $real && is_file($real) && str_starts_with($real, $root.DIRECTORY_SEPARATOR)) {
                $mime = mime_content_type($real) ?: 'application/octet-stream';
                $mime = str_ends_with($real, '.svg') ? 'image/svg+xml' : (str_ends_with($real, '.css') ? 'text/css' : $mime);

                return new Response((string) file_get_contents($real), 200, ['Content-Type' => $mime]);
            }
        }
        if (! isset($types[$file])) {
            return new Response(Lang::t('ui.not_found'), 404, ['Content-Type' => 'text/plain']);
        }

        return new Response(
            (string) file_get_contents(dirname(__DIR__, 2).'/resources/assets/'.$file),
            200,
            ['Content-Type' => $types[$file].'; charset=utf-8'],
        );
    }

    /** @param list<string> $keys */
    private function currentIndex(array $keys): int
    {
        foreach ($keys as $i => $key) {
            if (! $this->ctx->state->isDone($key)) {
                return $i;
            }
        }

        return count($keys) - 1;
    }

    /** @return array<string, mixed> */
    private function layoutData(?StepInterface $active): array
    {
        return [
            'alba' => $this->alba,
            'ctx' => $this->ctx,
            'token' => $this->token,
            'steps' => $this->steps,
            'active' => $active?->key(),
            'done' => $this->ctx->state->get('done', []),
            'locale' => $this->translator->locale,
            'dir' => $this->translator->direction(),
            'languages' => $this->alba->languageSwitcher ? $this->translator->available() : [],
            'hasCustomCss' => (bool) ($this->alba->extraCss && is_file($this->alba->extraCss)),
            'hasThemeCss' => (bool) ($this->alba->themePack?->cssFile() && is_file($this->alba->themePack->cssFile())),
            'tokensCss' => \SpykraLabs\Alba\Themes\Tokens::css($this->alba),
            'logo' => $this->alba->theme['logo'] ?? $this->alba->themePack?->logo(),
        ];
    }
}
