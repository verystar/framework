<?php

namespace Very;

/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 15/2/13 下午11:29.
 */

use Very\Http\Exception\HttpResponseException;

class View
{
    private static $temp_data = array();

    protected $view_path = '';

    /**
     * All of the finished, captured sections.
     *
     * @var array
     */
    protected $sections = [];

    protected $extends = [];

    protected $datas = [];

    /**
     * The stack of in-progress sections.
     *
     * @var array
     */
    protected $sectionStack = [];

    public function setPath($path)
    {
        $this->view_path = realpath($path . '/') . DIRECTORY_SEPARATOR;
    }

    public function getPath()
    {
        return $this->view_path;
    }

    public function exists($__path)
    {
        return file_exists($this->getPath() . $__path);
    }

    /**
     * 获取模板的值，而不直接输出.
     *
     * @param string $__path    路径
     * @param array  $__datas   数据
     * @param string $__charset 编码
     *
     * @return string
     *
     * @throws \Very\Http\Exception\HttpResponseException
     */
    public function get($__path, $__datas = array(), $__charset = null)
    {
        $ob_level = ob_get_level();
        ob_start();
        $this->datas = $__datas;

        if ($__charset == null) {
            $__charset = config('app.charset');
        }
        static $is_header = false;
        if (!$is_header) {
            $is_header = true;
            header('Content-type: text/html; charset=' . $__charset);
        }
        if (file_exists($this->getPath() . $__path)) {
            $__datas = array_merge(self::$temp_data, $__datas);
            if ($__datas) {
                extract($__datas);
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
     * 设置模板全局变量.
     *
     * @param $data
     */
    public function set($data)
    {
        self::$temp_data = array_merge(self::$temp_data, $data);
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
            $this->display($file . '.php', $this->datas);
        }
        $this->flush();
    }
}
