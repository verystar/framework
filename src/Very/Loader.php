<?php namespace Very;
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 15/2/16 下午5:43
 */

class Loader {
    /**
     * 替代require_once
     *
     * @param $file
     *
     * @return bool
     */
    public function import($file) {
        static $import_files = [];

        if (!isset($import_files[$file])) {
            if (is_file($file)) {
                require $file;
                $import_files[$file] = 1;
                return true;
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * 加载自定义函数
     * @static
     *
     * @param string $class_name 类库名
     *
     * @return object
     * @throws Exception
     */
    public function helper($class_name) {
        static $instances = array();
        $class_name = strtolower($class_name);
        $path       = app('path.helpers');
        if (!isset($instances[$path . $class_name])) {
            self::import($path . $class_name . '.php');
        }
    }

    /**
     * 加载model
     *
     * @param $model
     *
     * @return object
     * @throws Exception
     */
    public function model($model) {
        $classname = implode('/', array_map('ucfirst', explode('/', $model))) . 'Model';
        $classname = '\\Models\\' . str_replace("/", "\\", $classname);

        static $instances = array();

        if (!isset($instances[$classname])) {
            if (class_exists($classname)) {
                $instances[$classname] = new $classname;
            } else {
                throw new Exception('Model ' . $classname . ' not found.', Exception::ERR_NOTFOUND_MODEL);
            }
        }
        return $instances[$classname];
    }
}