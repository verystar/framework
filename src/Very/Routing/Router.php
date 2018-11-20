<?php

namespace Very\Routing;

/**
 * 路由操作库
 *
 * @author caixudong
 */
use Exception;
use RuntimeException;
use Very\Http\FormRequest;
use Very\Contracts\Debug\ExceptionHandler;
use Very\Http\Exception\HttpResponseException;

class Router
{
    protected $controllerName;
    protected $actionName;
    protected $middleware = [];

    public function getControllerName()
    {
        return $this->controllerName;
    }

    public function setControllerName($controller)
    {
        return $this->controllerName = strtolower($controller);
    }

    public function getActionName()
    {
        return $this->actionName;
    }

    public function setActionName($action)
    {
        return $this->actionName = strtolower($action);
    }

    public function dispatch()
    {
        $uri    = request()->path();
        $params = $uri == '/' ? [] : explode('/', $uri);

        if ($params) {
            $last_params = array_pop($params);
            $ext         = pathinfo($last_params);
            if (isset($ext['extension']) && in_array(strtolower($ext['extension']), ['json', 'msgpack', 'appcache'])) {
                $params[] = $ext['filename'];
                request()->setParam('extension', strtolower($ext['extension']));
            } else {
                $params[] = $last_params;
            }
            unset($ext);
        }

        $action = $params ? array_pop($params) : 'index';

        foreach ($params as $param) {
            if (!preg_match('/([a-zA-Z]\w*)/', $param)) {
                throw new RuntimeException("Class names can not begin with a number: " . $param);
            }
        }

        $controller = $params ? implode('/', $params) : 'index';

        $this->setControllerName($controller);
        $this->setActionName($action);

        try {
            $this->handle($controller, $action);
        } catch (Exception $e) {
            app()->make(ExceptionHandler::class)->render($e);
        }
    }

    private function handle($controller, $action)
    {
        $controllername = $this->getControllerClassName($controller);

        if (!class_exists($controllername)) {
            throw new HttpResponseException("Unable to load class: " . $controllername, HttpResponseException::ERR_NOTFOUND_CONTROLLER);
        }

        if (!method_exists($controllername, $action . 'Action')) {
            throw new HttpResponseException($action . 'Action method not found in ' . $controllername, HttpResponseException::ERR_NOTFOUND_ACTION);
        }

        $instance = app()->make($controllername);
        //controller middleware
        $this->resolveMiddleware($instance->getMiddleware());
        $action     = $action . 'Action';
        $reflector  = new \ReflectionMethod($instance, $action);
        $parameters = $this->resolveMethodDependencies($reflector);

        call_user_func_array([$instance, $action], $parameters);
    }

    /**
     * Get all of the defined middleware short-hand names.
     *
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    public function pushMiddleware($middlewares)
    {
        $middlewares = is_array($middlewares) ? $middlewares : [$middlewares];
        foreach ($middlewares as $middleware) {
            if (!in_array($middleware, $this->middleware)) {
                $this->middleware[] = $middleware;
            }
        }
        return $this;
    }

    public function resolveMiddleware($middlewares)
    {
        foreach ($middlewares as $middleware) {
            $instance = app()->make($middleware);
            $instance->handle();
        }
    }


    /**
     * Resolve the given method's type-hinted dependencies.
     *
     * @param  \ReflectionFunctionAbstract $reflector
     *
     * @return array
     */
    private function resolveMethodDependencies(\ReflectionFunctionAbstract $reflector)
    {
        $parameters = [];
        foreach ($reflector->getParameters() as $key => $parameter) {
            $class = $parameter->getClass();
            if ($class) {
                //Request
                $instance = app()->make($class->name);

                if ($instance instanceof FormRequest) {
                    if ($instance->authorize()) {
                        $instance->validate();
                    } else {
                        $instance->forbiddenResponse();
                    }
                }

                array_splice(
                    $parameters, $key, 0, [$instance]
                );
            }
        }

        return $parameters;
    }

    private function getNamespace()
    {
        return app('namespace') ? '\\' . app('namespace') : '';
    }

    private function getControllerClassName($controller)
    {
        $controllername = implode('\\', array_map('ucfirst', explode('/', strtolower($controller))));
        $controllername = $this->getNamespace() . '\\Http\\Controllers\\' . $controllername . 'Controller';
        return $controllername;
    }
}