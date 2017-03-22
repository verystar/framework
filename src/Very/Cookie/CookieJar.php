<?php
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 15/2/16 下午4:18.
 */
namespace Very\Cookie;

use Very\Support\Arr;

class CookieJar
{
    /**
     * cookie prefix
     *
     * @var string
     */
    private $prefix = '';
    private $domain = null;

    public function setPrefix($prefix = '')
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Set domain.
     *
     * @param $domain
     *
     * @return $this
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * Get cookie
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key = null, $default = null)
    {
        if ($key) {
            $key = $this->prefix . $key;
            return Arr::get($_COOKIE, $key, $default);
        } else {
            return $default;
        }
    }

    /**
     * Get all cookie.
     *
     * @return array
     */
    public function getAll()
    {
        return $_COOKIE;
    }

    /**
     * Set cookie
     *
     * @param        $key
     * @param        $value
     * @param int    $time
     * @param string $path
     * @param null   $domain
     * @param bool   $secure
     * @param bool   $httpOnly
     *
     * @return $this
     */
    public function set($key, $value, $time = 86400, $path = '/', $domain = null, $secure = false, $httpOnly = true)
    {
        if ($domain === null) {
            $domain = $this->domain;
        }
        $key = $this->prefix . $key;
        if (!is_cli()) {
            setcookie($key, $value, time() + $time, $path, $domain, $secure, $httpOnly);
        }
        $_COOKIE[$key] = $value;

        return $this;
    }

    /**
     * Remove cookie
     *
     * @param        $key
     * @param string $path
     * @param null   $domain
     */
    public function delete($key, $path = '/', $domain = null)
    {
        if ($domain === null) {
            $domain = $this->domain;
        }
        $key = $this->prefix . $key;
        if (!is_cli()) {
            setcookie($key, null, -2628000, $path, $domain);
        }
        unset($_COOKIE[$key]);
    }
}