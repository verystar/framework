<?php

namespace Very\Session;

class SessionManager
{
    /**
     * 判断是否启用session_start的标志符.
     *
     * @var bool
     */
    private $is_start = false;

    private $default_options = array(
        'session_save_path' => '',
        'session_type'      => 'file', //支持memcache,file,mysql
        'session_lefttime'  => 3600, //默认1小时
        'session_name'      => 'php_session',
    );

    public function __construct($options = array())
    {
        $options = array_merge($this->default_options, $options);

        if ($options['session_name']) {
            //设置session_name
            session_name($options['session_name']);
        }

        if ($options['session_lefttime']) {
            //设置最大生存时间
            $this->setLifeTime($options['session_lefttime']);
        }

        if ($options['session_type'] === 'file' && is_dir($options['session_save_path'])) {
            //存储路径
            session_save_path($options['session_save_path']);
        }

        if ($options['session_type'] == 'memcache') {
            ini_set('session.save_handler', 'memcache');
            ini_set('session.save_path', 'tcp://127.0.0.1:11211?timeout=' . $options['session_lefttime']);
            //pecl libmemcached扩展使用
            //ini_set("session.save_handler", "memcache");
            //ini_set("session.save_path", "127.0.0.1:11211");
            //使用多个 memcached server 时用逗号","隔开，并且和 Memcache::addServer() 文档中说明的一样，可以带额外的参数"persistent"、"weight"、"timeout"、"retry_interval" 等等，类似这样的："tcp://host1:port1?persistent=1&weight=2,tcp://host2:port2"
        }
    }

    public function setLifeTime($session_lefttime)
    {
        //设置最大生存时间
        ini_set('session.gc_maxlifetime', $session_lefttime);
        session_cache_expire($session_lefttime);
    }

    /**
     * 获取Session值
     *
     * @param string $key     需要获取的Session名
     * @param mixed  $default 当不存在需要获取的Session值时的默认值
     *
     * @return mixed 返回的Session值
     */
    public function get($key = null, $default = null)
    {
        $this->session_start();

        if ($key) {
            if (isset($_SESSION[$key])) {
                return $_SESSION[$key];
            } else {
                return $default;
            }
        } else {
            return $this->getAll();
        }
    }

    /**
     * 返回所有Session数据.
     *
     * @return array 返回的所有Session数据
     */
    public function getAll()
    {
        return $_SESSION;
    }

    /**
     * Put a key / value pair or array of key / value pairs in the session.
     *
     * @param string|array $key
     * @param mixed|null   $value
     *
     * @return $this;
     */
    public function put($key, $value = null)
    {
        if (!is_array($key)) {
            $key = array($key => $value);
        }

        foreach ($key as $arrayKey => $arrayValue) {
            $this->set($arrayKey, $arrayValue);
        }

        return $this;
    }

    /**
     * 设置Session值
     *
     * @param string $key   需要设置的Session名
     * @param mixed  $value 需要设置的Session值
     */
    public function set($key, $value)
    {
        $this->session_start();
        $_SESSION[$key] = $value;
    }

    /**
     * 删除某Session值，del()方法的别名.
     *
     * @see del
     */
    public function delete($key)
    {
        $this->session_start();
        unset($_SESSION[$key]);
    }

    /**
     * 销毁所有Session数据.
     */
    public function destroy()
    {
        $this->session_start();
        $this->is_start = false;
        $_SESSION       = array();
        session_destroy();
    }

    /**
     * @param null $id
     *
     * @return string
     */
    public function session_id($id = null)
    {
        if ($id === null) {
            $this->session_start();

            return session_id();
        } else {
            $this->close();
            session_id($id);
        }
    }

    /**
     * 启动Session.
     */
    private function session_start()
    {
        if (!$this->is_start) {
            session_start();
            $this->is_start = true;
        }
    }

    public function close()
    {
        session_write_close();
        $this->is_start = false;
    }

    public function __destruct()
    {
        $this->close();
    }
}
