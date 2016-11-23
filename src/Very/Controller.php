<?php

namespace Very;

/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 15/2/16 下午4:18.
 */

use Very\Http\Exception\HttpResponseException;

abstract class Controller
{
    public function __call($fun, $arg)
    {
        throw new HttpResponseException($fun . 'Action method not found file in: ' . request()->getControllerName() . 'Controller', HttpResponseException::ERR_NOTFOUND_ACTION);
    }

    /**
     * 请求转发.
     *
     * @param $controller
     * @param $action
     *
     * @throws HttpResponseException
     */
    protected function forward($controller, $action)
    {
        $params = func_get_args();
        app('router')->run($controller, $action, array_splice($params, 2));
    }
}
