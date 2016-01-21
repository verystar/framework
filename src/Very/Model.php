<?php namespace Very;

/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 15/2/16 下午4:17
 */

use Very\Database\Connection;
use Very\Database\Pager;
use Very\Cache\Redis;

abstract class Model {
    public $use_db = 'default';
    public $use_redis = 'default';

    /**
     * @param string $db
     *
     * @return \Very\Database\MysqlConnection
     */
    public function db($db = '') {
        $db = $db ? $db : $this->use_db;
        return Connection::getInstance($db);
    }

    /**
     * @param string $redis
     *
     * @return \Redis | \Very\Cache\Redis
     * @throws Exception
     */
    public function redis($redis = '') {
        $redis = $redis ? $redis : $this->use_redis;
        return Redis::getInstance($redis);
    }

    /**
     * @param int    $curr_page
     * @param int    $per_page
     * @param string $ct_db_name
     * @param string $select_db_name
     *
     * @return \Very\Database\Pager
     * @throws Exception
     */
    public function pager($curr_page = 1, $per_page = 10, $ct_db_name = '', $select_db_name = '') {
        $ct_db_name = $ct_db_name ? $ct_db_name : $this->use_db;
        if (!$select_db_name) {
            $select_db_name = $ct_db_name;
        }

        return new Pager(array('ct' => $this->db($ct_db_name), 'select' => $this->db($select_db_name)), $curr_page, $per_page);
    }

    /**
     * 使用单例模式调用一个model方法，eg model('user')->singleton()->getUser('1');
     * @return object
     */
    public function singleton() {
        $model = get_called_class();
        if (strtolower(substr($model, -5)) === 'model') {
            $model = substr($model, 0, -5);
        }
        $model_instance = model($model);
        $aa             = Singleton::getInstance($model_instance);
        return $aa;
    }
}