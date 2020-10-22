<?php

namespace Very\Http;

/**
 * Created by PhpStorm.
 * User: fifsky
 * Date: 15/2/16 下午5:29.
 */

use Very\Support\Str;
use Very\Support\Traits\Singleton;

class Request
{
    protected $params = [];
    use Singleton;

    protected $headers = [];

    public function __construct()
    {
        $this->initHeader();
    }

    private function initHeader()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $name           = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[strtolower($name)] = $value;
            } elseif (\in_array($name, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $headers[strtolower($name)] = $value;
            }
        }
        $this->headers = $headers;
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

    public function header($key, $default = null)
    {
        $key = strtolower($key);
        return isset($this->headers[$key]) ? $this->headers[$key] : $default;
    }

    public function headers()
    {
        return $this->headers;
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
            return $_GET + $_POST;
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

    public function file($key)
    {
        return $_FILES[$key];
    }

    public function isRequest($method)
    {
        if ($this->server('REQUEST_METHOD') == $method) {
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
        return $this->server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
    }

    public function server($index = '', $default = '')
    {
        if (!$index) {
            return $_SERVER;
        }

        return $this->fetchArray($_SERVER, $index, $default);
    }

    public function getQueryString()
    {
        return $this->server('QUERY_STRING');
    }

    /**
     * Checks whether the request is secure or not.
     *
     * X-Forwarded-Proto proxy not support
     *
     * @return bool
     */
    public function isSecure()
    {
        $https = $this->server('HTTPS');
        return !empty($https) && 'off' !== strtolower($https) || (int)$this->server('SERVER_PORT') === 443 || $this->server('HTTP_X_FORWARDED_PROTO') === 'https';
    }

    /**
     * Gets the request's scheme.
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    /**
     * Returns the HTTP host being requested.
     *
     * not support custom port
     *
     * @return string
     */
    public function getHost()
    {
        if (!$host = $this->server('SERVER_NAME')) {
            $host = $this->server('SERVER_ADDR');
        }

        return $host;
    }

    public function url()
    {
        return $this->getScheme() . '://' . $this->getHost() . $this->uri();
    }

    public function uri()
    {
        if (!($uri = $this->server('REQUEST_URI'))) {
            if (null !== $qs = $this->getQueryString()) {
                $qs = '?' . $qs;
            }

            $uri = $this->server('PHP_SELF') . $qs;
        }
        return $uri;
    }

    /**
     * Get the current path info for the request.
     *
     * @return string
     */
    public function path()
    {
        $pattern = trim(parse_url($this->uri(), PHP_URL_PATH), '/');

        return $pattern == '' ? '/' : $pattern;
    }

    /**
     * Get the current encoded path info for the request.
     *
     * @return string
     */
    public function decodedPath()
    {
        return rawurldecode($this->path());
    }

    /**
     * Determine if the current request URI matches a pattern.
     * @return bool
     */
    public function is()
    {
        foreach (func_get_args() as $pattern) {
            if (Str::is($pattern, $this->decodedPath())) {
                return true;
            }
        }

        return false;
    }
}
