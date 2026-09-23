<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Adapters;

use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use SpykraLabs\Alba\Alba;
use SpykraLabs\Alba\Http\Request;

/**
 * Laravel adapter. Create `alba.php` in the project root returning an Alba
 * instance; the provider mounts it at Alba's route, outside the web
 * middleware group so it works before .env / the database exist.
 */
final class LaravelServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $file = base_path('alba.php');
        if (! is_file($file)) {
            return;
        }

        /** @var Alba $alba */
        $alba = (require $file)->basePath(base_path());
        $handler = function (LaravelRequest $request) use ($alba) {
            $out = $alba->handle(new Request(
                $request->method(),
                '/'.ltrim($request->getPathInfo(), '/'),
                $request->query->all(),
                $request->request->all(),
                $request->cookies->all(),
                array_map(fn ($v) => (string) $v[0], $request->headers->all()),
                (string) $request->ip(),
            ));

            $response = response($out->body, $out->status, $out->headers);
            foreach ($out->cookies as $name => $value) {
                $response->headers->setCookie(new \Symfony\Component\HttpFoundation\Cookie($name, $value, 0, '/', null, null, true, false, 'lax'));
            }

            return $response;
        };

        Route::withoutMiddleware('*')
            ->match(['GET', 'POST'], rtrim($alba->route, '/').'/{any?}', $handler)
            ->where('any', '.*');
    }
}
