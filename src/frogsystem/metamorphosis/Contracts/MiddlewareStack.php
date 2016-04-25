<?php
namespace Frogsystem\Metamorphosis\Contracts;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface MiddlewareStack
 * @package Frogsystem\Metamorphosis\Contracts
 */
interface MiddlewareStack
{
    /**
     * @param $middleware
     * @return MiddlewareStack
     */
    public function push($middleware);

    /**
     * Process the middleware stack
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param mixed $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next);
}
