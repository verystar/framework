<?php namespace Very;
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 15/2/16 下午4:18
 */

class Controller {

    public function __call($fun, $arg) {
        throw new Exception($fun . 'Action method not found file in: ' . request()->getControllerName() . 'Controller', Exception::ERR_NOTFOUND_ACTION);
    }

    /**
     * 请求转发
     *
     * @param $controller
     * @param $action
     */
    protected function forward($controller, $action) {
        $params = func_get_args();

        if (count($params) >1 ) {
            request()->setControllerName($params[0]);
            request()->setActionName($params[1]);

        } else {
            request()->setControllerName('error');
            request()->setActionName('error');
        }

        $loader = app('loader');
        $controller_instance  = call_user_func_array([$loader,'controller'],array_splice($params,2));
        $controller_instance->{$action.'Action'}();
    }
}