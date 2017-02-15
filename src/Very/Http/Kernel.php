<?php

namespace Very\Http;

use Very\Routing\Router;

abstract class Kernel
{
    /**
     * The router instance.
     *
     * @var \Very\Routing\Router
     */
    protected $router;

    /**
     * The application's middleware stack.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [];

    /**
     * Create a new HTTP kernel instance.
     *
     * @param  \Very\Routing\Router $router
     */
    public function __construct(Router $router)
    {
        $class_name = get_called_class();
        app()->set("namespace", str_replace('\Http\Kernel', '', $class_name));

        foreach ($this->middleware as $middleware) {
            $instance = app()->make($middleware);
            $instance->handle();
        }

        $this->router = $router;
        foreach ($this->routeMiddleware as $key => $middlewares) {
            if (request()->is($key)) {
                $middlewares = is_array($middlewares) ? $middlewares : [$middlewares];
                foreach ($middlewares as $middleware) {
                    $instance = app()->make($middleware);
                    $instance->handle();
                }
                break;
            }
        }

        $this->router->dispatch();
    }
}