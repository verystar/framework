<?php

namespace Very;

/**
 * 路由操作库，调用方法library('router').
 *
 * @author caixudong
 */
use Very\Http\Exception\HttpResponseException;
use RuntimeException;

class Router
{
    public function init()
    {
        $uri    = request()->uri();
        $uri    = trim(parse_url($uri, PHP_URL_PATH), '/');
        $params = isset($uri[0]) ? explode('/', $uri) : [];

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

        request()->setControllerName($controller);
        request()->setActionName($action);

        try {
            $this->run($controller, $action);
        } catch (HttpResponseException $e) {
            $controllername = $this->getNamespace() . '\\Exceptions\\Handler';
            app()->make($controllername)->render($e);
        }
    }

    private function run($controller, $action, $params = [])
    {
        $controllername = $this->getControllerClassName($controller);

        if (!class_exists($controllername, false)) {
            throw new HttpResponseException("Unable to load class: " . $controllername, HttpResponseException::ERR_NOTFOUND_CONTROLLER);
        }

        if (!method_exists($controllername, $action . 'Action')) {
            throw new HttpResponseException($action . 'Action method not found in ' . $controllername, HttpResponseException::ERR_NOTFOUND_ACTION);
        }

        $instance  = app()->make($controllername, $params);
        $action    = $action . 'Action';
        $reflector = new \ReflectionMethod($instance, $action);

        /*
         * action 自动注入
         */
        $parameters = [];
        foreach ($reflector->getParameters() as $key => $parameter) {
            $class = $parameter->getClass();
            if ($class) {
                array_splice(
                    $parameters, $key, 0, [app()->make($class->name)]
                );
            }
        }

        call_user_func_array([$instance, $action], $parameters);
    }

    private function getNamespace()
    {
        $controller_namespace = app('namespace.controller') ? '\\' . app('namespace.controller') : '';
        if (!$controller_namespace) {
            $controller_namespace = app('namespace') ? '\\' . app('namespace') : '';
        }

        return $controller_namespace;
    }

    private function getControllerClassName($controller)
    {
        $controller_namespace = $this->getNamespace();
        $controllername       = implode('/', array_map('ucfirst', explode('/', strtolower($controller))));
        $controllername       = str_replace('/', '\\', $controllername);
        $controllername       = $controller_namespace . '\\Controllers\\' . $controllername . 'Controller';

        return $controllername;
    }
}