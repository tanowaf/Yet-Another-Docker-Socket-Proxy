<?php
declare(strict_types=1);

namespace TanoWAF\YaDSP\Proxy;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TanoWAF\WAFCore\Proxy\FixedUpstreamProxy;
use TanoWAF\WAFCore\Server\MiddlewareAware;

/// @todo rename?
class DSFilteringProxy extends MiddlewareAware
{
    /**
     * Generates an "access denied" response: mimic what the Docker daemon returns by default for not-accepted requests,
     * but give a specific error text.
     * @todo make it easy to change this from config
     */
    protected function deniedResponse(ServerRequestInterface $request, \Throwable|null $e = null): ResponseInterface
    {
        $this->debug("Access denied for request: " . $this->request2Log($request));
        // Mimic what the Docker daemon returns by default for not-accepted requests - but give a specific error text
        // (docker says "page not found" for 404s)
        return new Response(404, ['content-type' => 'application/json'], json_encode(['message' => 'access denied']));
    }

    /**
     * Generates an "error happened" response: mimic what the Docker daemon returns by default for not-accepted requests,
     * but give a specific error text.
     * @todo make it easy to change this from config
     * @todo allow setting a 'debug' mode in which the returned json includes the full exception message
     */
    protected function errorResponse(ServerRequestInterface|null $request = null, \Throwable|null $e = null): ResponseInterface
    {
        if ($request !== null) {
            $this->warning('Upstream connection error for request: ' . $this->request2Log($request) . ' Error:' . $e->getMessage());
        }
/// @todo... make sure we mimic correctly what the Docker daemon returns by default for failed requests (try to we trigger one...)
        return self::getErrorResponse($e);
    }

    public static function getErrorResponse(\Throwable|null $e = null): ResponseInterface
    {
        return new Response(
            500,
            ['content-type' => 'application/json'],
            json_encode(['message' => 'error' . ($e ? ' ' . $e->getMessage() : '')])
        );
    }
}
