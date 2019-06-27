<?php

namespace Very;

/*
 * Created by PhpStorm.
 * User: fifsky
 * Date: 15/2/16 下午4:17
 */

use RuntimeException;
use Very\Database\Connection;
use Very\Database\Pager;
use Very\Cache\Redis;

abstract class Model
{
    public $use_db    = 'default';
    public $use_redis = 'default';

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_at';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey;

    /**
     * The colum key for the model.
     *
     * @var string
     */
    protected $columns = [];

    private function checkModelConfiguration()
    {
        if (!$this->primaryKey || !$this->table || !$this->columns) {
            throw new RuntimeException('Invalid model configuration:' . get_called_class());
        }
    }

    /**
     * @param string $db
     *
     * @return \Very\Database\MysqlConnection
     */
    protected function db($db = '')
    {
        $db = $db ? $db : $this->use_db;

        return Connection::getInstance($db);
    }

    /**
     * @param string $redis
     *
     * @return \Redis | \Very\Cache\Redis
     */
    protected function redis($redis = '')
    {
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
     */
    protected function pager($curr_page = 1, $per_page = 10, $ct_db_name = '', $select_db_name = '')
    {
        $ct_db_name = $ct_db_name ? $ct_db_name : $this->use_db;
        if (!$select_db_name) {
            $select_db_name = $ct_db_name;
        }

        return new Pager(array('ct' => $this->db($ct_db_name), 'select' => $this->db($select_db_name)), $curr_page, $per_page);
    }

    public function get($id)
    {
        $this->checkModelConfiguration();
        $where    = [];
        $bind_arr = [];
        if (is_array($id)) {
            $bind_arr = filter_field($id, $this->columns);
            foreach ($bind_arr as $key => $value) {
                $where[] = "{$key} = :{$key}";
            }
            $where = implode(' and ', $where);
        } else {
            $where                       = "{$this->primaryKey} = :{$this->primaryKey}";
            $bind_arr[$this->primaryKey] = $id;
        }

        $sql = "select * from {$this->table} where {$where} limit 1";
        return $this->db()->getRow($sql, $bind_arr);
    }

    public function insert($data)
    {
        $this->checkModelConfiguration();

        $data = filter_field($data, $this->columns);
        if (!$data) {
            return false;
        }

        if (in_array(static::CREATED_AT, $this->columns)) {
            $data[static::CREATED_AT] = date('Y-m-d H:i:s');
        }

        if (in_array(static::UPDATED_AT, $this->columns)) {
            $data[static::UPDATED_AT] = $data[static::CREATED_AT];
        }

        unset($data[$this->primaryKey]);
        return $this->db()->insert($this->table, $data);
    }

    public function update($data)
    {
        $this->checkModelConfiguration();

        if (!is_array($data) || !isset($data[$this->primaryKey])) {
            return false;
        }

        $data = filter_field($data, $this->columns);
        if (!$data) {
            return false;
        }

        if (in_array(static::UPDATED_AT, $this->columns)) {
            $data[static::UPDATED_AT] = date('Y-m-d H:i:s');
        }

        return $this->db()->update($this->table, "{$this->primaryKey} = :{$this->primaryKey}", $data, [$this->primaryKey]);
    }

    public function delete($data)
    {
        $this->checkModelConfiguration();
        return $this->db()->delete($this->table, "{$this->primaryKey} = :{$this->primaryKey}", [$this->primaryKey => $data]);
    }
}