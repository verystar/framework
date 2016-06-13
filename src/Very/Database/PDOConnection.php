<?php
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 1/19/16 4:51 PM.
 */

namespace Very\Database;

use PDO;
use PDOException;
use Very\Library\FStat;

class PDOConnection extends PDO
{
    protected $connect_time = 0;
    protected $execute_time = 0;
    private $dsn;
    private $sql;
    private $is_stat = true; //是否监控统计
    private $error_code;
    private $error_info;
    private $driver_options;
    private $username;
    private $password;

    public function connect($dsn, $username, $password, $driver_options = array())
    {
        try {
            $start_time           = microtime(true);
            $this->dsn            = $dsn;
            $this->username       = $username;
            $this->password       = $password;
            $this->driver_options = $driver_options;
            parent::__construct($dsn, $username, $password, array(
                    PDO::ATTR_CASE    => PDO::CASE_LOWER,
                    PDO::ATTR_TIMEOUT => 1,
                ) + $driver_options);
            $this->connect_time = number_format(microtime(true) - $start_time, 6);

            if ($this->is_stat) {
                $_stat = FStat::getInstance();
                //监控SQL连接效率
                if ($this->getConnTime()) {
                    $_stat->set(1, 'SQL连接效率', $_stat->formatTime($this->getConnTime()), $this->dsn);
                }
            }
        } catch (PDOException $e) {
            //监控SQL连接错误
            FStat::getInstance()->set(1, 'BUG错误', 'SQL连接错误', $dsn, $e->getMessage());
            throw new \PDOException("DB connect error for dns $dsn:" . $e->getMessage());
        }
    }

    public function selectLimit($sql, $limit, $limit_from = 0, $params = array(), $driver_options = array())
    {
        $sql .= ' limit :limit_from,:limit';

        return $this->getAll($sql, array_merge($params, array('limit' => (int)$limit, 'limit_from' => (int)$limit_from)), $driver_options);
    }

    //返回数据以及是否含有下一页
    public function getPager($sql, $page, $num = 0, $params = array(), $driver_options = array())
    {
        $sql .= ' limit :limit_from,:limit';
        $ret   = $this->getAll($sql, array_merge($params, array('limit' => (int)$num + 1, 'limit_from' => ($page - 1) * (int)$num)), $driver_options);
        $count = count($ret);
        $count > $num && array_pop($ret);

        return array(
            'rs'   => $ret,
            'next' => $count > $num ? true : false,
        );
    }

    public function getAll($sql, $params = array(), $driver_options = array(), $fetch_style = PDO::FETCH_ASSOC)
    {
        $stmt = $this->execute($sql, $params, $driver_options);
        $all  = $stmt->fetchAll($fetch_style);

        return $all;
    }

    public function getOneAll($sql, $params = array(), $column_number = 0, $driver_options = array())
    {
        $stmt = $this->execute($sql, $params, $driver_options);
        $all  = array();
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $all[] = $row[$column_number];
        }

        return $all;
    }

    public function getRow($sql, $params = array(), $driver_options = array(), $fetch_style = PDO::FETCH_ASSOC)
    {
        $stmt = $this->execute($sql, $params, $driver_options);
        $row  = $stmt->fetch($fetch_style);

        return $row;
    }

    public function getOne($sql, $params = array(), $driver_options = array(), $column_number = 0)
    {
        $stmt = $this->execute($sql, $params, $driver_options);
        $one  = $stmt->fetchColumn($column_number);

        return $one;
    }

    public function execute($sql, $params = array(), $driver_options = array())
    {
        for ($i = 0; $i < 2; ++$i) {
            $start_time = microtime(true);
            $stmt       = $this->prepare($sql, $driver_options);
            $this->sql  = $sql;

            if ($stmt) {
                foreach ($params as $k => &$param) {
                    $stmt->bindParam($k, $param, PDO::PARAM_STR, strlen($param));
                }
                $stmt->execute();
            }

            $end_time           = microtime(true);
            $this->execute_time = number_format($end_time - $start_time, 6);
            $this->error_code   = $stmt ? $stmt->errorCode() : $this->errorCode();
            $this->error_info   = $stmt ? $stmt->errorInfo() : $this->errorInfo();

            //在常驻进程中，DB连接会丢失，但是并不会抛出异常，因此需要判断错误的值为2006就需要重新连接DB，然后再次执行query
            if ($this->isError() && $this->error_info[1] == 2006) {
                $this->connect($this->dsn, $this->username, $this->password, $this->driver_options);
                continue;
            }

            if ($this->is_stat) {
                $_stat = FStat::getInstance();

                //监控SQL错误
                if ($this->isError()) {
                    $debug       = $this->debug();
                    $debug['参数'] = $params;
                    logger()->error('SQL Error', $debug);
                    $_stat->set(1, 'BUG错误', 'SQL执行错误', $sql, json_encode($this->getErrorInfo()), 100);
                }

                //监控SQL执行效率
                if ($this->getExecTime()) {
                    $_stat->set(1, 'SQL执行效率', $_stat->formatTime($this->getExecTime()), $this->sql);
                }
            }
            break;
        }

        return $stmt;
    }

    public function debug()
    {
        return [
            'DSN'  => $this->dsn,
            'SQL'  => $this->sql,
            '状态'   => $this->getErrorCode(),
            '错误'   => $this->getErrorInfo(),
            '连接耗时' => $this->getConnTime(),
            '执行耗时' => $this->getExecTime(),
        ];
    }

    //是否开启监控统计
    public function setStat($is_stat)
    {
        $this->is_stat = $is_stat;

        return $this;
    }

    public function update($table, $where, $params = array(), $where_field = array())
    {
        if (strpos($where, '=') < 1 || !$params) {
            return false;
        }

        $sets = array();
        foreach ($params as $k => $v) {
            if (!in_array($k, $where_field)) {
                $sets[] = ' `' . $k . '` =:' . $k;
            }
        }

        $set = implode(',', $sets);
        $sql = "update {$table} set $set where $where";

        $ret = $this->execute($sql, $params);

        if ($ret->errorCode() != '00000') {
            return false;
        }

        return $ret;
    }

    public function insert($table, $params = array())
    {
        if (!$params) {
            return false;
        }

        $keys         = array_keys($params);
        $fileds       = '`' . implode('`,`', $keys) . '`';
        $filed_values = ':' . implode(',:', $keys);

        $sql = "insert into {$table}($fileds) values($filed_values)";
        $ret = $this->execute($sql, $params);
        if ($ret->rowCount()) {
            return $this->lastInsertId();
        } else {
            return false;
        }
    }

    public function delete($table, $where, $params)
    {
        if (!$params) {
            return false;
        }

        $sql = "delete from {$table} where $where";
        $ret = $this->execute($sql, $params);

        if ($ret->errorCode() != '00000') {
            return false;
        }

        return $ret;
    }

    public function getConnTime()
    {
        return $this->connect_time;
    }

    public function getExecTime()
    {
        return $this->execute_time;
    }

    public function isError()
    {
        return $this->error_code != '00000';
    }

    public function getErrorCode()
    {
        return $this->error_code;
    }

    public function getErrorInfo()
    {
        return $this->error_info;
    }
}
