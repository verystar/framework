<?php

namespace Very\Http;

/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 15/2/16 下午5:29.
 */
class Request
{
    protected $_controller;
    protected $_action;
    protected $params = [];

    public static function getInstance()
    {
        static $_instance = null;

        return $_instance ?: $_instance = new self();
    }

    private function fetchArray($array, $index = '', $default = null)
    {
        if (!isset($array[$index])) {
            return $default;
        }

        return $array[$index];
    }

    public function setParam($key, $val)
    {
        $this->params[$key] = $val;
    }

    public function getParam($key, $default = null)
    {
        return $this->fetchArray($this->params, $key, $default);
    }

    public function getParams()
    {
        return $this->params;
    }

    public function get($index = '', $default = null)
    {
        if ($index) {
            return $this->fetchArray($_GET, $index, $default);
        } else {
            return $_GET;
        }
    }

    public function post($index = '', $default = null)
    {
        if ($index) {
            return $this->fetchArray($_POST, $index, $default);
        } else {
            return $_POST;
        }
    }

    public function all($index = '', $default = null)
    {
        if (!$index) {
            return $_REQUEST;
        }

        if (!isset($_POST[$index])) {
            return $this->get($index, $default);
        } else {
            return $this->post($index, $default);
        }
    }

    public function put($key = null, $default = null)
    {
        parse_str(file_get_contents('php://input'), $_PUT);
        if (empty($key)) {
            return isset($_PUT) ? $_PUT : null;
        } else {
            return $this->fetchArray($_PUT, $key, $default);
        }
    }

    public function del($key)
    {
        parse_str(file_get_contents('php://input'), $_DEL);

        return $this->fetchArray($_DEL, $key);
    }

    public function isRequest($method)
    {
        $request_method = $_SERVER['REQUEST_METHOD'];
        if ($request_method == $method) {
            return true;
        }

        return false;
    }

    public function isCli()
    {
        return PHP_SAPI == 'cli';
    }

    public function isPost()
    {
        return $this->isRequest('POST');
    }

    public function isPut()
    {
        return $this->isRequest('PUT');
    }

    public function isDel()
    {
        return $this->isRequest('DEL') || $this->isRequest('DELETE');
    }

    public function isGet()
    {
        return $this->isRequest('GET');
    }

    public function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    public function server($index = '', $default = '')
    {
        if (!$index) {
            return $_SERVER;
        }

        return $this->fetchArray($_SERVER, $index, $default);
    }

    public function getControllerName()
    {
        return $this->_controller;
    }

    public function setControllerName($controller)
    {
        return $this->_controller = strtolower($controller);
    }

    public function getActionName()
    {
        return $this->_action;
    }

    public function setActionName($action)
    {
        return $this->_action = strtolower($action);
    }
}
