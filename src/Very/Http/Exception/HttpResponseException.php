<?php
namespace Very\Http\Exception;
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 15/1/28 下午1:58.
 */
use RuntimeException;
class HttpResponseException extends RuntimeException
{
    const ERR_NOTFOUND_CONTROLLER = 1;
    const ERR_NOTFOUND_ACTION = 2;
    const ERR_NOTFOUND_VIEW = 4;
    public function __construct($message = null, $code = 0)
    {
        parent::__construct($message, $code);
    }
}