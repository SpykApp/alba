<?php

declare(strict_types=1);

namespace SpykraLabs\Alba\Adapters;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SpykraLabs\Alba\Alba;
use SpykraLabs\Alba\Http\Request;

/**
 * PSR-15 adapter (Symfony PSR bridge, Slim, Mezzio, Laminas...). Requests under
 * the configured route are answered by Alba; everything else passes through.
 * Requires psr/http-server-middleware and a PSR-17 response factory.
 */
final class Psr15Middleware implements MiddlewareInterface
{
    public function __construct(private Alba $alba, private ResponseFactoryInterface $responses) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $route = rtrim($this->alba->route, '/');
        if ($path !== $route && ! str_starts_with($path, $route.'/')) {
            return $handler->handle($request);
        }

        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            $headers[strtolower($name)] = implode(',', $values);
        }

        $alba = $this->alba->handle(new Request(
            $request->getMethod(),
            $path,
            $request->getQueryParams(),
            (array) $request->getParsedBody(),
            $request->getCookieParams(),
            $headers,
            (string) ($request->getServerParams()['REMOTE_ADDR'] ?? ''),
        ));

        $response = $this->responses->createResponse($alba->status);
        foreach ($alba->headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        foreach ($alba->cookies as $name => $value) {
            $response = $response->withAddedHeader('Set-Cookie', "$name=$value; Path=/; HttpOnly; SameSite=Lax");
        }
        $response->getBody()->write($alba->body);

        return $response;
    }
}
