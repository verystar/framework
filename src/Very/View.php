<?php namespace Very;

/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 15/2/13 下午11:29
 */

Class View {

    private static $temp_data = array();

    protected $view_path = '';

    public function setPath($path) {
        $this->view_path = realpath($path.'/') . DIRECTORY_SEPARATOR;
    }

    public function getPath() {
        return $this->view_path;
    }

    /**
     * 模板输出，如果不用函数直接include不利于公用模板的变量隔离
     *
     * @param string $__path    路径
     * @param array  $__datas   数据
     * @param string $__charset 编码
     *
     * @return string
     * @throws Exception
     */
    public function display($__path, $__datas = array(), $__charset = NULL) {
        if ($__charset == NULL) {
            $__charset = config('app', 'charset');
        }
        static $is_header = false;
        if (!$is_header) {
            $is_header = true;
            header("Content-type: text/html; charset=" . $__charset);
        }
        if (file_exists($this->getPath() . $__path)) {
            $__datas = array_merge(self::$temp_data, $__datas);
            if ($__datas) {
                extract($__datas);
            }

            include $this->getPath() . $__path;
        } else {
            throw new Exception('Not found View file in: ' . $this->getPath() . $__path, Exception::ERR_NOTFOUND_VIEW);
        }
        return NULL;
    }

    /**
     * 获取模板的值，而不直接输出
     *
     * @param       $view
     * @param array $data
     * @param null  $charset
     *
     * @return string
     */
    public function get($view, $data = array(), $charset = NULL) {
        ob_start();
        $this->display($view, $data, $charset);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    /**
     * 设置模板全局变量
     *
     * @param $data
     */
    public function set($data) {
        self::$temp_data = array_merge(self::$temp_data, $data);
    }

    public function request() {
        return request();
    }
}