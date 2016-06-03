<?php

namespace Very\Http;

/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 15/2/16 下午5:29.
 */
class Response
{
    // CONTENT TYPE
    const JSON = 'application/json';
    const HTML = 'text/html';
    const JAVASCRIPT = 'text/javascript';
    const JS = 'text/javascript';
    const CSS = 'text/css';
    const TEXT = 'text/plain';
    const XML = 'text/xml';

    public static function getInstance()
    {
        static $_instance = null;

        return $_instance ?: $_instance = new self();
    }

    public function exec($type, $data)
    {
        call_user_func(array($this, $type), $data);
    }

    public function json($data = array())
    {
        header('Content-type: '.self::JSON.'; charset='.config('app', 'charset'));
        if (!is_array($data)) {
            if (is_object($data)) {
                $data = get_object_vars($data);
            } else {
                $data = array();
            }
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function msgpack($data = array())
    {
        if (function_exists('msgpack_pack')) {
            echo msgpack_pack($data);
        }
    }

    public function string($data = '')
    {
        header('Content-type: '.self::TEXT.'; charset='.config('app', 'charset'));
        echo $data;
    }

    public function xml($data = '')
    {
        header('Content-type: '.self::XML.'; charset='.config('app', 'charset'));
        echo $data;
    }
}
