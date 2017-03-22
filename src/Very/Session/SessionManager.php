<?php

namespace Very\Session;

use Very\Support\Arr;

class SessionManager
{
    /**
     * Session store started status.
     *
     * @var bool
     */
    private $started = false;

    private $default_options = array(
        'session_save_path' => '',
        'session_type'      => 'file', //memcache,file,mysql
        'session_lefttime'  => 3600, //1 hour
        'session_name'      => 'php_session',
    );

    public function __construct($options = array())
    {
        $options = array_merge($this->default_options, $options);

        if ($options['session_name']) {
            session_name($options['session_name']);
        }

        if ($options['session_lefttime']) {
            $this->setLifeTime($options['session_lefttime']);
        }

        if ($options['session_type'] === 'file' && is_dir($options['session_save_path'])) {
            session_save_path($options['session_save_path']);
        }

        if ($options['session_type'] == 'memcache') {
            ini_set('session.save_handler', 'memcache');
            $host = $options['host'] ? $options['host'] : '127.0.0.1';
            $port = $options['port'] ? $options['port'] : 11211;
            ini_set('session.save_path', 'tcp://' . $host . ':' . $port . '?timeout=' . $options['session_lefttime']);
        }
    }

    public function setLifeTime($session_lefttime)
    {
        ini_set('session.gc_maxlifetime', $session_lefttime);
        session_cache_expire($session_lefttime);
    }


    /**
     * Checks if an a key is present and not null.
     *
     * @param  string|array $key
     *
     * @return bool
     */
    public function has($key)
    {
        return !collect(is_array($key) ? $key : func_get_args())->contains(function ($key) {
            return is_null($this->get($key));
        });
    }

    /**
     * Get an item from the session.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key = null, $default = null)
    {
        $this->start();

        if ($key) {
            return Arr::get($_SESSION, $key, $default);
        } else {
            return $this->getAll();
        }
    }

    /**
     * Get all item from the session.
     *
     * @return array
     */
    public function getAll()
    {
        return $_SESSION;
    }

    /**
     * Put a key / value pair or array of key / value pairs in the session.
     *
     * @param  string|array $key
     * @param  mixed        $value
     *
     * @return void
     */
    public function put($key, $value = null)
    {
        if (!is_array($key)) {
            $key = [$key => $value];
        }

        foreach ($key as $arrayKey => $arrayValue) {
            Arr::set($_SESSION, $arrayKey, $arrayValue);
        }
    }

    /**
     * Set an item from the session.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value)
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    /**
     * Remove one or many items from the session.
     *
     * @param  string $keys
     *
     * @return void
     */
    public function delete($keys)
    {
        $this->start();
        Arr::forget($_SESSION, $keys);
    }

    /**
     *  Destroy session.
     * @return void
     */
    public function destroy()
    {
        $this->start();
        $this->started = false;
        $_SESSION      = array();
        session_destroy();
    }

    /**
     * Get session id
     *
     * @return string
     */
    public function getId()
    {
        $this->start();
        return session_id();
    }

    /**
     * Set session id
     *
     * @param null $id
     *
     * @return void
     */
    public function setId($id = null)
    {
        $this->close();
        session_id($id);
    }

    /**
     * Started session.
     */
    public function start()
    {
        if (!$this->started) {
            if (!is_cli()) {
                session_start();
            };
            $this->started = true;
        }
    }

    public function close()
    {
        session_write_close();
        $this->started = false;
    }

    /**
     * Determine if the session has been started.
     *
     * @return bool
     */
    public function isStarted()
    {
        return $this->started;
    }

    public function __destruct()
    {
        $this->close();
    }
}