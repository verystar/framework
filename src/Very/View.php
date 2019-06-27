<?php

namespace Very;

/**
 * Created by PhpStorm.
 * User: fifsky
 * Date: 15/2/13 下午11:29.
 */

use Very\Http\Exception\HttpResponseException;

class View
{

    protected $path = '';

    /**
     * All of the finished, captured sections.
     *
     * @var array
     */
    protected $sections = [];

    protected $extends = [];

    /**
     * The array of view global data.
     *
     * @var array
     */
    protected $global_data = [];
    protected $data        = [];

    protected $composers = [];

    /**
     * The stack of in-progress sections.
     *
     * @var array
     */
    protected $sectionStack = [];

    public function setPath($path)
    {
        $this->path = realpath($path . '/') . DIRECTORY_SEPARATOR;
    }

    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get the array of view data.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    public function exists($__path)
    {
        return file_exists($this->getPath() . $__path);
    }

    public function composer($paths, $composer)
    {
        if (!is_array($paths)) {
            $paths = [$paths];
        }
        foreach ($paths as $path) {
            if (!isset($this->composers[$path]) || !in_array($composer, $this->composers[$path])) {
                $this->composers[$path][] = $composer;
            }
        }
    }


    /**
     * 添加全局数据
     *
     * @param  string|array $key
     * @param  mixed        $value
     *
     * @return $this
     */
    public function set($key, $value = null)
    {
        if (is_array($key)) {
            $this->global_data = array_merge($this->global_data, $key);
        } else {
            $this->global_data[$key] = $value;
        }

        return $this;
    }

    /**
     * 获取模板的值，而不直接输出.
     *
     * @param string $__path    路径
     * @param array  $__data    数据
     * @param string $__charset 编码
     *
     * @return string
     *
     * @throws \Very\Http\Exception\HttpResponseException
     */
    public function get($__path, $__data = array(), $__charset = null)
    {
        //TODO 这里是传递给extends的模板,此处由于是单例并且extends最后执行,如果在view里面再次使用view()->display()则会覆盖上次的data,因此View需要使用工厂模式产生新的实例,未实现
        $this->data = $__data;
        //composer
        if (isset($this->composers[$__path]) && $this->composers[$__path]) {
            foreach ($this->composers[$__path] as $composer) {
                $__composer_data = app()->make($composer)->compose($__data);
                $__data          = array_merge($__data, (array)$__composer_data);
            }
        }

        $ob_level = ob_get_level();
        ob_start();
        $__data = array_merge($this->global_data, $__data);

        if ($__charset == null) {
            $__charset = config('app.charset');
        }
        static $is_header = false;
        if (!$is_header) {
            $is_header = true;
            header('Content-type: text/html; charset=' . $__charset);
        }
        if (file_exists($this->getPath() . $__path)) {
            if ($__data) {
                extract($__data);
            }
            // We'll evaluate the contents of the view inside a try/catch block so we can
            // flush out any stray output that might get out before an error occurs or
            // an exception is thrown. This prevents any partial views from leaking.
            try {
                include $this->getPath() . $__path;
            } catch (\Exception $e) {
                $this->handleViewException($e, $ob_level);
            }
        } else {
            throw new HttpResponseException('Not found View file in: ' . $this->getPath() . $__path, HttpResponseException::ERR_NOTFOUND_VIEW);
        }

        return ltrim(ob_get_clean());
    }

    /**
     * 直接渲染模板输出.
     *
     * @param       $view
     * @param array $data
     * @param null  $charset
     *
     * @return string
     */
    public function display($view, $data = array(), $charset = null)
    {
        echo $this->get($view, $data, $charset);
    }

    /**
     * Handle a view exception.
     *
     * @param \Exception $e
     * @param int        $obLevel
     *
     * @throws $e
     */
    protected function handleViewException($e, $obLevel)
    {
        while (ob_get_level() > $obLevel) {
            ob_end_clean();
        }
        throw $e;
    }

    /**
     * Start injecting content into a section.
     *
     * @param string $section
     */
    public function start($section)
    {
        if (ob_start()) {
            $this->sectionStack[] = $section;
        }
    }

    /**
     * Stop injecting content into a section and return its contents.
     *
     * @return string
     */
    public function yieldContnet()
    {
        return $this->content($this->stop());
    }

    /**
     * Stop injecting content into a section.
     *
     * @param bool $overwrite
     *
     * @return string
     */
    public function stop($overwrite = false)
    {
        $last = array_pop($this->sectionStack);
        if ($overwrite) {
            $this->sections[$last] = ob_get_clean();
        } else {
            $content = ob_get_clean();
            if (isset($this->sections[$last])) {
                $content = str_replace('@parent', $content, $this->sections[$last]);
            }

            $this->sections[$last] = $content;
        }

        return $last;
    }

    /**
     * extend layouts base.
     *
     * @param string $file
     */
    public function extend($file)
    {
        $this->extends[$file] = $file;
    }

    /**
     * Get the string contents of a section.
     *
     * @param string $section
     * @param string $default
     *
     * @return string
     */
    public function content($section, $default = '')
    {
        $sectionContent = $default;
        if (isset($this->sections[$section])) {
            $sectionContent = $this->sections[$section];
        }

        return $sectionContent;
    }

    /**
     * Flush all of the section contents.
     */
    public function flush()
    {
        $this->sections     = [];
        $this->sectionStack = [];
    }

    /**
     * Check if section exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return array_key_exists($name, $this->sections);
    }

    /**
     * Get the entire array of sections.
     *
     * @return array
     */
    public function getSections()
    {
        return $this->sections;
    }

    public function __destruct()
    {
        foreach ($this->extends as $file) {
            $this->display($file . '.php', $this->data);
        }
        $this->flush();
    }
}