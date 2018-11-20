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
        $this->router = $router;

        //global middleware
        $this->router->resolveMiddleware($this->middleware);

        //router middleware
        foreach ($this->routeMiddleware as $key => $middlewares) {
            if (request()->is($key)) {
                $this->router->pushMiddleware($middlewares);
                break;
            }
        }
        $this->router->resolveMiddleware($this->router->getMiddleware());

        $this->router->dispatch();
    }
}