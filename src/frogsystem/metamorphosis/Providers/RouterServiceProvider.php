<?php
namespace Frogsystem\Metamorphosis\Providers;

use Aura\Router\Map;
use Aura\Router\RouterContainer;
use Aura\Router\Rule\RuleIterator;
use Frogsystem\Metamorphosis\Middleware\RouterMiddleware;
use Frogsystem\Metamorphosis\WebApplication;
use Frogsystem\Spawn\Container;

/**
 * Class RouterServiceProvider
 * @package Frogsystem\Metamorphosis\Providers
 */
class RouterServiceProvider extends ServiceProvider
{
    /**
     * Registers entries with the container.
     * @param Container $app
     */
    public function register(Container $app)
    {
        $routerContainer = new RouterContainer();
        $app[Map::class] = $routerContainer->getMap();
        $app[RuleIterator::class] = $routerContainer->getRuleIterator();

        // If huggable is a container
        if ($app instanceof WebApplication) {
            $app->add(RouterMiddleware::class);
        }
    }
}
