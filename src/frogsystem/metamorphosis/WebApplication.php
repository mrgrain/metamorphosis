<?php
namespace Frogsystem\Metamorphosis;

use Exception;
use Frogsystem\Metamorphosis\Constrains\GroupHugTrait;
use Frogsystem\Metamorphosis\Constrains\HuggableTrait;
use Frogsystem\Metamorphosis\Contracts\GroupHuggable;
use Frogsystem\Metamorphosis\Middleware\RouterMiddleware;
use Frogsystem\Metamorphosis\Middleware\Stack;
use Frogsystem\Metamorphosis\Providers\ConfigServiceProvider;
use Frogsystem\Metamorphosis\Providers\HttpServiceProvider;
use Frogsystem\Metamorphosis\Providers\RouterServiceProvider;
use Frogsystem\Spawn\Container;
use Interop\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmitterInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\SapiEmitter;

/**
 * Class WebApplication
 * @property ServerRequestInterface request
 * @property EmitterInterface emitter
 * @package Frogsystem\Metamorphosis
 */
class WebApplication extends Container implements GroupHuggable
{
    use HuggableTrait;
    use GroupHugTrait;

    /**
     * @var array
     */
    private $huggables = [
        RouterServiceProvider::class,
        HttpServiceProvider::class,
        ConfigServiceProvider::class,
    ];

    /**
     * @var array|Stack
     */
    protected $middleware;

    /**
     * @param ContainerInterface $delegate
     */
    public function __construct(ContainerInterface $delegate = null)
    {
        // call parent constructor
        parent::__construct($delegate);

        // set default application instance
        $this->set(self::class, $this);
        $this->set(get_called_class(), $this);

        // middleware
        $this->middleware = new Stack();

        // set emitter
        $this->emitter = $this->factory(SapiEmitter::class);

        // hugging
        $this->huggables = $this->load($this->huggables);
        $this->groupHug($this->huggables);
    }

    /**
     * @param $middleware
     * @return $this
     */
    public function add($middleware)
    {
        // wrap middleware with custom functionality
        $this->middleware->push(function (ServerRequestInterface $request, ResponseInterface $response, $next) use ($middleware) {
            // store current request to container
            $this->request = $request;

            // Make the middleware if necessary
            if (is_string($middleware)) {
                $middleware = $this->make($middleware);
            }

            // run middleware
            return $middleware($request, $response, $next);
        });
        return $this;
    }

    /**
     * @param $huggables
     * @return mixed
     * @deprecated
     * @throws \Frogsystem\Spawn\Exceptions\InvalidArgumentException
     */
    protected function load($huggables)
    {
        // Connect Huggables
        foreach ($huggables as $key => $huggable) {
            if (is_string($huggable)) {
                $huggable = $this->make($huggable);
                $huggables[$key] = $huggable;
            }
        }

        return $huggables;
    }


    /**
     * @param ServerRequestInterface|null $request
     * @param ResponseInterface|null $response
     * @param Callable|null $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request = null, ResponseInterface $response = null, $next = null)
    {
        // Read Request if omitted
        if (is_null($request)) {
            $request = $this->get(ServerRequestInterface::class);
        }

        // Create Response if omitted
        if (is_null($response)) {
            $response = $this->get(ResponseInterface::class);
        }

        // wrap middleware into exception terminator
        $wrapper = function (ServerRequestInterface $request, ResponseInterface $response, $next) {
            // Try to run through the stack, catch any exceptions
            try {
                return $this->middleware->__invoke($request, $response, $next);
            } catch (Exception $exception) {
                return $this->terminate($request, $response, $exception);
            }
        };


        if (is_null($next)) {
            $next = function (ServerRequestInterface $request, ResponseInterface $response) {
                return $response;
            };

            // Nothing left to do, emit the response
            $wrapper = function (ServerRequestInterface $request, ResponseInterface $response, $next) use ($wrapper) {
                return $this->emitter->emit($wrapper($request, $response, $next));
            };
        }

        // Application is called as a regular middleware, continue with stack
        return $wrapper($request, $response, $next);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param Exception $exception
     * @return HtmlResponse
     */
    public function terminate(ServerRequestInterface $request, ResponseInterface $response, Exception $exception)
    {
        // Exception template
        $template = <<<HTML
<!DOCTYPE html>
<html>
    <head>
        <title>There was an error with your application</title>
    </head>
    <body>
        <h1>Quack! Something went wrong...</h1>
        <p>
            <strong>{$exception->getMessage()}</strong>
        </p>
        <pre>{$exception->getTraceAsString()}</pre>
    </body>
</html>
HTML;

        // Create Error Response
        return new HtmlResponse($template, 501);
    }
}
