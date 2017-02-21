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
    /**
     * The middleware registered on the controller.
     *
     * @var array
     */
    protected $middleware = [];

    public function __call($fun, $arg)
    {
        throw new HttpResponseException($fun . 'Action method not found file in: ' . router()->getControllerName() . 'Controller', HttpResponseException::ERR_NOTFOUND_ACTION);
    }

    /**
     * Get the middleware assigned to the controller.
     *
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }
}
