<?php namespace Very;

/**
 * 路由操作库，调用方法library('router')
 * @author caixudong
 */


class Router {

    public function init($rewrite = true) {


        $default_controller = config('app', 'default_controller', 'index');
        $default_action     = config('app', 'default_action', 'index');

        if ($rewrite) {
            $uri = $_SERVER['REQUEST_URI'];
            $uri = parse_url($uri, PHP_URL_PATH);

            if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0) {
                $uri = substr($uri, strlen($_SERVER['SCRIPT_NAME']));
            }

            $uri = trim(trim($uri, '/'));

            $params = strlen($uri) > 0 ? explode('/', $uri) : array();

            $count_params = count($params);

            if ($count_params) {
                $last_params = &$params[$count_params - 1];
                $ext         = pathinfo($last_params);
                if (isset($ext['extension']) && in_array(strtolower($ext['extension']), ['json', 'msgpack', 'appcache'])) {
                    $last_params = $ext['filename'];
                    request()->setParam('extension', strtolower($ext['extension']));
                    unset($ext);
                }
            }

            do {
                //目前仅仅简单的实现第一段为controller第二段为action，其他的都不考虑
                if ($count_params == 1) {
                    if (preg_match('/([a-zA-Z]\w*)/', $params[0])) {
                        $controller = config('app', 'default_controller', 'index');
                        $action     = $params[0];
                    } else {
                        $controller = 'error';
                        $action     = 'error';
                    }
                    break;
                }

                if ($count_params > 1) {
                    $is_val = true;
                    foreach ($params as $param) {
                        if (!preg_match('/([a-zA-Z]\w*)/', $param)) {
                            $is_val = false;
                            break;
                        }
                    }

                    if ($is_val) {
                        $action     = array_pop($params);
                        $controller = implode('/', $params);

                    } else {
                        $controller = 'error';
                        $action     = 'error';
                    }
                    break;
                }

                $controller = $default_controller;
                $action     = $default_action;
            } while (0);

        } else {
            $controller = request()->get('m', $default_controller);
            $action     = request()->get('a', $default_action);
        }

        request()->setControllerName($controller);
        request()->setActionName($action);

        try {

            $controllername = strtolower($controller . 'Controller');
            $controllername = strtolower(str_replace("/", "\\", $controllername));
            if (app('controller.namespace')) {
                $controllername = '\\' . app('controller.namespace') . '\\' . $controllername;
            }

            if (!method_exists($controllername, $action . 'Action')) {
                throw new Exception($action . 'Action method not found in ' . $controllername, Exception::ERR_NOTFOUND_ACTION);
            }

            app('loader')->controller()->{$action . 'Action'}();
        } catch (Exception $e) {
            request()->setControllerName('error');
            request()->setActionName('error');

            app('loader')->controller()->errorAction($e);
        }
    }
}