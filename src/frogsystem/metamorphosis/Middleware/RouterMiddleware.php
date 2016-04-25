<?php
namespace Frogsystem\Metamorphosis\Middleware;

use Aura\Router\Matcher;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class RouterMiddleware
 * @package Frogsystem\Metamorphosis\Middleware
 */
class RouterMiddleware
{
    /**
     * @var Matcher The route matcher.
     */
    protected $matcher;

    /**
     * @param Matcher $matcher
     */
    public function __construct(Matcher $matcher)
    {
        $this->matcher = $matcher;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     * @throws \Exception
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        // Get route
        $route = $this->matcher->match($request);
        if (!$route) {
            throw new \Exception('Not found', 404);
        }

        // Store attributes
        foreach ($route->attributes as $key => $val) {
            $request = $request->withAttribute($key, $val);
        }

        // Invoke with response and route attributes
        $middleware = $route->handler;
        return $middleware($request, $response, $next);
    }
}
