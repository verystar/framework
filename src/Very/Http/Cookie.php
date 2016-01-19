<?php namespace Very\Http;
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 15/2/16 下午4:18
 */


class Cookie {
    /**
     * 存储Cookie的命名前缀
     * @access private
     * @var string
     */
    private $prefix = '';
    private $domain = null;


    public function setPrefix($prefix = '') {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * 全局控制domain
     * @param $domain
     *
     * @return $this
     */
    public function setDomain($domain) {
        $this->domain = $domain;
        return $this;
    }

    /**
     * 获取Cookie值
     * @access public
     *
     * @param string $key     需要获取的Cookie名
     * @param mixed  $default 当不存在需要获取的Cookie值时的默认值
     *
     * @return mixed 返回的Cookie值
     */
    public function get($key = null, $default = null) {
        if ($key) {
            $key = $this->prefix . $key;
            if (isset($_COOKIE[$key])) {
                return $_COOKIE[$key];
            } else {
                return $default;
            }
        } else {
            return $default;
        }
    }

    /**
     * 返回所有Cookie数据
     * @access public
     * @return array 返回的所有Cookie数据
     */
    public function getAll() {
        return $_COOKIE;
    }

    /**
     * 设置Cookie值
     * @access public
     *
     * @param string $key    需要设置的Cookie名
     * @param string $value  需要设置的Cookie值
     * @param int    $time   时间 秒
     * @param string $path   路径
     * @param string $domain 域
     * @return $this
     */
    public function set($key, $value, $time = 86400, $path = '/', $domain = NULL) {
        if ($domain === NULL) {
            $domain = $this->domain;
        }
        $key = $this->prefix . $key;
        setcookie($key, $value, time() + $time, $path, $domain);
        //立即生效
        $_COOKIE[$key] = $value;

        return $this;
    }

    /**
     * 删除某Cookie值
     * @access public
     *
     * @param string $key    需要设置的Cookie名
     * @param int    $time   时间 秒
     * @param string $path   路径
     * @param string $domain 域
     */
    public function del($key, $time = 86400, $path = '/', $domain = NULL) {
        if ($domain === NULL) {
            $domain = $this->domain;
        }
        $key = $this->prefix . $key;
        setcookie($key, NULL, time() - $time, $path, $domain);
    }

    /**
     * @param        $key
     * @param int    $time
     * @param string $path
     * @param null   $domain
     * @see del
     */
    public function delete($key, $time = 86400, $path = '/', $domain = NULL) {
        if ($domain === NULL) {
            $domain = $this->domain;
        }
        $key = $this->prefix . $key;
        $this->del($key, $time, $path, $domain);
    }
}