<?php
namespace Frogsystem\Metamorphosis\Providers;

use Aura\Router\Map;
use Frogsystem\Spawn\Container;
use Frogsystem\Spawn\Contracts\PluggableInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ServiceProvider
 * @package Frogsystem\Metamorphosis\Providers
 */
abstract class RoutesProvider
{
    /**
     * @var Container The app container.
     */
    protected $app;

    /**
     * @var Map The route map.
     */
    protected $map;

    /**
     * @var string The base namespace of the controllers.
     */
    protected $namespace;

    /**
     * @param Container $app
     * @param Map $map
     */
    public function __construct(Container $app, Map $map)
    {
        $this->app = $app;
        $this->map = $map;
    }

    /**
     * @param $controller
     * @param $method
     * @return callable
     */
    public function controller($controller, $method)
    {
        // Prepend namespace
        if ($this->namespace && 0 !== strpos($controller, "\\")) {
            $controller = $this->namespace."\\".$controller;
        }

        // Add controller to app if necessary
        if (!$this->app->has($controller)) {
            $this->app[$controller] = $this->app->one($controller);
        }

        // Return closure
        $controller = $this->app->get($controller);
        return [$controller, $method];
    }
}
