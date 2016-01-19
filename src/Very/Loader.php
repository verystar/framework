<?php namespace Very;
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 15/2/16 下午5:43
 */

class Loader {

    private static $is_register = false;

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

    public function register($autoload = NULL) {
        if (!self::$is_register) {
            $autoload = $autoload === NULL ? array($this, 'autoload') : $autoload;
            spl_autoload_register($autoload);
            self::$is_register = true;
        }
    }

    public function unregister($autoload = NULL) {
        if (self::$is_register) {
            $autoload = $autoload === NULL ? array($this, 'autoload') : $autoload;
            spl_autoload_unregister($autoload);
        }
    }

    protected function autoload($class_name) {

        $class_name = ltrim(strtolower(str_replace("\\", "/", $class_name)), '/');

        $file = '';
        do {
            $type = substr($class_name, -5);
            if ($type === "model") {
                $file = app('path.models') . substr($class_name, 0, -5);
                break;
            }

            $type = substr($class_name, -10);
            if ($type === "controller") {
                if (app('controller.namespace')) {
                    $len                  = strlen(app('controller.namespace'));
                    $class_name_namespace = substr($class_name, 0, $len);

                    if ($class_name_namespace == app('controller.namespace')) {
                        $class_name = substr($class_name, $len + 1);
                    }
                }

                $file = app('path.controllers') . substr($class_name, 0, -10);
                break;
            }

        } while (0);

        if ($file) {
            $file = $file . ".php";
            self::import($file);
        }
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
     * 加载控制器
     * @return mixed
     * @throws Exception
     */
    public function controller() {

        $classname = request()->getControllerName();
        $classname = strtolower($classname . 'Controller');
        $classname = strtolower(str_replace("/", "\\", $classname));

        if (app('controller.namespace')) {
            $classname = '\\' . app('controller.namespace') . '\\' . $classname;
        }

        $params = func_get_args();

        static $instances = array();

        if (!isset($instances[$classname])) {

            if (class_exists($classname)) {

                if ($params) {
                    //带参数的控制层构造函数实例化需要用反射API实例化
                    $class                 = new \ReflectionClass($classname);
                    $instances[$classname] = $class->newInstanceArgs($params);
                } else {
                    $instances[$classname] = new $classname();
                }
            } else {
                throw new Exception('Controller ' . $classname . ' not found.', Exception::ERR_NOTFOUND_CONTROLLER);
            }
        }
        return $instances[$classname];
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

        $classname = strtolower($model . 'Model');
        $classname = strtolower(str_replace("/", "\\", $classname));

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