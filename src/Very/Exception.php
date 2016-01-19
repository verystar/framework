<?php namespace Very;
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 15/1/28 下午1:58
 */

class Exception extends \Exception {

    const ERR_NOTFOUND_CONTROLLER = 1;
    const ERR_NOTFOUND_ACTION = 2;
    const ERR_NOTFOUND_MODEL = 3;
    const ERR_NOTFOUND_VIEW = 4;
    const ERR_NOTFOUND_MODULE = 5;

    public function __construct($message = null, $code = 0) {
        parent::__construct($message, $code);
    }
}
