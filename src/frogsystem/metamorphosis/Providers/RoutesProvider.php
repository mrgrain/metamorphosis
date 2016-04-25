<?php
namespace Frogsystem\Metamorphosis\Providers;

use Aura\Router\Map;
use Frogsystem\Metamorphosis\Constrains\HuggableTrait;
use Frogsystem\Metamorphosis\Contracts\Huggable;
use Frogsystem\Spawn\Container;
use Interop\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ServiceProvider
 * @package Frogsystem\Metamorphosis\Providers
 */
abstract class RoutesProvider implements Huggable
{
    use HuggableTrait {
        hug as protected returnHug;
    }

    /**
     * @var Container The app container.
     */
    protected $app;

    /**
     * @var string The base namespace of the controllers.
     */
    protected $namespace;

    /**
     * @param Container $app
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    /**
     * Create a controller middleware. Wraps the passed callable in a middleware closure and invokes it from the container.
     * If the controller is not a callable, build one from a class and method name.
     * @param callable|string $controller
     * @param string|null $method
     * @return callable
     */
    public function controller($controller, $method = null)
    {
        return function (ServerRequestInterface $request, ResponseInterface $response, callable $next) use ($controller, $method) {
            // make a controller from class and method name
            if (!is_null($method)) {
                $controller = [$this->app->make($controller), $method];
            }

            return $this->app->invoke($controller, array_merge([
                'Psr\Http\Message\ResponseInterface' => $next($request, $response),
                'Psr\Http\Message\ServerRequestInterface' => $request,
            ], $request->getAttributes()));
        };
    }

    /**
     * Implementation of the HuggableInterface
     * @param Huggable $huggable
     */
    public function hug(Huggable $huggable)
    {
        // If huggable is a container
        if ($huggable instanceof ContainerInterface && $huggable->has(Map::class)) {
            $this->registerRoutes($huggable->get(Map::class));
        }
        $this->returnHug($this);
    }

    /**
     * @param Map $map
     * @return mixed
     */
    abstract protected function registerRoutes(Map $map);
}
