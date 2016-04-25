<?php
namespace Frogsystem\Metamorphosis\Middleware;

use Frogsystem\Metamorphosis\Contracts\MiddlewareStack;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SplStack;

/**
 * Class Stack
 * @package Frogsystem\Metamorphosis\Middleware
 */
class Stack extends SplStack implements MiddlewareStack
{
    /**
     * Build a middleware stack from any number for arguments.
     */
    public function __construct()
    {
        foreach (func_get_args() as $middleware) {
            $this->push($middleware);
        }
    }

    /**
     * Process the middleware stack
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        /** @var callable $middleware The next middleware. */
        if (!$this->isEmpty()) {
            // Run the next Middleware
            $middleware = $this->pop();
            return $middleware($request, $response, function (ServerRequestInterface $request, ResponseInterface $response) use ($next) {
                return $this->__invoke($request, $response, $next);
            });
        }

        return $next($request, $response);
    }
}
