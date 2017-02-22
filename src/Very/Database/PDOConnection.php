<?php
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 1/19/16 4:51 PM.
 */

namespace Very\Database;

use PDO;
use Exception;
use PDOException;
use LogicException;
use Very\Support\Str;

class PDOConnection
{
    /**
     * The default PDO connection options.
     *
     * @var array
     */
    protected $options = [
        PDO::ATTR_CASE       => PDO::CASE_NATURAL,
//        PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_AUTOCOMMIT => 1,
//        PDO::ATTR_ORACLE_NULLS      => PDO::NULL_NATURAL,
//        PDO::ATTR_STRINGIFY_FETCHES => false,
//        PDO::ATTR_EMULATE_PREPARES  => false,
    ];

    /**
     * The default fetch mode of the connection.
     *
     * @var int
     */
    protected $fetchMode = PDO::FETCH_ASSOC;

    /**
     * The database connection time
     * @var int
     */
    protected $connectTime = 0;

    /**
     * If lost conntection reconnect
     * @var callable
     */
    protected $reconnector;

    /**
     * Current sql execute error code
     * @var string
     */
    private $errorCode;

    /**
     * Current sql execute error info
     * @var array
     */
    private $errorInfo;

    /**
     * All of the queries run against the connection.
     *
     * @var array
     */
    protected $queryLog = [];

    /**
     * Indicates whether queries are being logged.
     *
     * @var bool
     */
    protected $loggingQueries = false;


    /**
     * @var \PDO
     */
    protected $pdo;

    public function connect($dsn, $username, $password, $options = array())
    {
        try {
            $start_time    = microtime(true);
            $this->options = $this->getOptions($options);

            try {
                $this->pdo = $this->createPdoConnection(
                    $dsn, $username, $password, $this->options
                );
            } catch (Exception $e) {
                $this->pdo = $this->tryAgainIfCausedByLostConnection(
                    $e, $dsn, $username, $password, $this->options
                );
            }

            $this->connectTime = $this->getElapsedTime($start_time);
            //监控SQL连接效率
            if ($this->getConnTime()) {
                mstat()->set(1, 'SQL连接效率', mstat()->formatTime($this->getConnTime()), $dsn);
            }
        } catch (PDOException $e) {
            //监控SQL连接错误
            mstat()->set(1, 'BUG错误', 'SQL连接错误', $dsn, $e->getMessage());
            throw new \PDOException("DB connect error for dns $dsn:" . $e->getMessage());
        }
    }

    /**
     * Set the reconnect instance on the connection.
     *
     * @param  callable $reconnector
     *
     * @return $this
     */
    public function setReconnector(callable $reconnector)
    {
        $this->reconnector = $reconnector;

        return $this;
    }

    /**
     * Reconnect to the database.
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function reconnect()
    {
        if (is_callable($this->reconnector)) {
            return call_user_func($this->reconnector, $this);
        }

        throw new LogicException('Lost connection and no reconnector available.');
    }

    /**
     * Create a new PDO connection instance.
     *
     * @param  string $dsn
     * @param  string $username
     * @param  string $password
     * @param  array  $options
     *
     * @return \PDO
     */
    protected function createPdoConnection($dsn, $username, $password, $options)
    {
        return new PDO($dsn, $username, $password, $options);
    }


    /**
     * Determine if the given exception was caused by a lost connection.
     *
     * @param  \Exception $e
     *
     * @return bool
     */
    protected function causedByLostConnection(Exception $e)
    {
        $message = $e->getMessage();

        return Str::contains($message, [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
        ]);
    }

    /**
     * Handle an exception that occurred during connect execution.
     *
     * @param  \Exception $e
     * @param  string     $dsn
     * @param  string     $username
     * @param  string     $password
     * @param  array      $options
     *
     * @return \PDO
     *
     * @throws \Exception
     */
    protected function tryAgainIfCausedByLostConnection(Exception $e, $dsn, $username, $password, $options)
    {
        if ($this->causedByLostConnection($e)) {
            return $this->createPdoConnection($dsn, $username, $password, $options);
        }

        throw $e;
    }

    /**
     * Get the PDO options based on the configuration.
     *
     * @param  array $options
     *
     * @return array
     */
    public function getOptions(array $options)
    {
        return array_diff_key($this->options, $options) + $options;
    }

    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * Get limit reulst
     *
     * @param       $sql
     * @param       $limit
     * @param int   $limit_from
     * @param array $params
     *
     * @return array
     */
    public function selectLimit($sql, $limit, $limit_from = 0, $params = array())
    {
        $sql .= ' limit :limit_from,:limit';

        return $this->getAll($sql, array_merge($params, array('limit' => (int)$limit, 'limit_from' => (int)$limit_from)));
    }

    /**
     * Get result and next assertions
     *
     * @param       $sql
     * @param       $page
     * @param int   $num
     * @param array $params
     *
     * @return array
     */
    public function getPager($sql, $page, $num = 0, $params = array())
    {
        $sql .= ' limit :limit_from,:limit';
        $ret   = $this->getAll($sql, array_merge($params, array('limit' => (int)$num + 1, 'limit_from' => ($page - 1) * (int)$num)));
        $count = count($ret);
        $count > $num && array_pop($ret);

        return array(
            'rs'   => $ret,
            'next' => $count > $num ? true : false,
        );
    }

    /**
     * Get all result
     *
     * @param       $sql
     * @param array $params
     *
     * @return array
     */
    public function getAll($sql, $params = array())
    {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll($this->fetchMode);
    }

    /**
     * Get one column together into an array
     *
     * @param       $sql
     * @param array $params
     * @param int   $column_number
     *
     * @return array
     */
    public function getOneAll($sql, $params = array(), $column_number = 0)
    {
        $stmt = $this->execute($sql, $params);
        $all  = array();
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $all[] = $row[$column_number];
        }

        return $all;
    }

    /**
     * Get a single record
     *
     * @param       $sql
     * @param array $params
     *
     * @return mixed
     */
    public function getRow($sql, $params = array())
    {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetch($this->fetchMode);
    }

    /**
     * Get one column result
     *
     * @param       $sql
     * @param array $params
     * @param int   $column_number
     *
     * @return string
     */
    public function getOne($sql, $params = array(), $column_number = 0)
    {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchColumn($column_number);
    }


    /**
     * Bind values to their parameters in the given statement.
     *
     * @param  \PDOStatement $statement
     * @param  array         $bindings
     *
     * @return void
     */
    public function bindValues($statement, $bindings)
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue(
                is_string($key) ? $key : $key + 1, $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
    }


    /**
     * Execute sql
     * @param       $sql
     * @param array $params
     *
     * @return \PDOStatement
     */
    public function execute($sql, $params = array())
    {
        for ($i = 0; $i < 2; ++$i) {
            $start_time = microtime(true);
            $stmt       = $this->getPdo()->prepare($sql);
            $this->bindValues($stmt, $params);
            $stmt->execute();
            $this->errorCode = $stmt->errorCode();
            $this->errorInfo = $stmt->errorInfo();

            //在常驻进程中，DB连接会丢失，但是并不会抛出异常，因此需要判断错误的值为2006就需要重新连接DB，然后再次执行query
            if ($this->errorCode != '00000' && $this->errorInfo[1] == 2006) {
                $this->reconnect();
                continue;
            }

            $execute_time = $this->getElapsedTime($start_time);

            $this->logQuery(
                $sql, $params, $execute_time, $this->errorInfo
            );

            //监控SQL错误
            if ($this->errorCode != '00000') {
                logger()->error('SQL Error', [$sql, $params, $execute_time]);
                mstat()->set(1, 'BUG错误', 'SQL执行错误', $sql, json_encode($this->getErrorInfo()), 100);
            }

            //监控SQL执行效率
            if ($execute_time) {
                mstat()->set(1, 'SQL执行效率', mstat()->formatTime($execute_time), $sql);
            }
            break;
        }

        return $stmt;
    }

    /**
     * Log a query in the connection's query log.
     *
     * @param  string     $query
     * @param  array      $bindings
     * @param  float|null $time
     * @param  array      $info
     *
     * @return void
     */
    public function logQuery($query, $bindings, $time = null, $info)
    {
        if ($this->loggingQueries) {
            $this->queryLog[] = compact('query', 'bindings', 'time', 'info');
        }
    }

    /**
     * Get the connection query log.
     *
     * @return array
     */
    public function getQueryLog()
    {
        return $this->queryLog;
    }

    /**
     * Clear the query log.
     *
     * @return void
     */
    public function flushQueryLog()
    {
        $this->queryLog = [];
    }

    /**
     * Enable the query log on the connection.
     *
     * @return $this
     */
    public function enableQueryLog()
    {
        $this->loggingQueries = true;
        return $this;
    }

    /**
     * Disable the query log on the connection.
     *
     * @return $this
     */
    public function disableQueryLog()
    {
        $this->loggingQueries = false;
        return $this;
    }

    /**
     * Determine whether we're logging queries.
     *
     * @return bool
     */
    public function logging()
    {
        return $this->loggingQueries;
    }

    /**
     * Get the elapsed time since a given starting point.
     *
     * @param  float $start_time
     *
     * @return float
     */
    protected function getElapsedTime($start_time)
    {
        return number_format(microtime(true) - $start_time, 6);
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
            return $this->getPdo()->lastInsertId();
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
        return $this->connectTime;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    public function getErrorInfo()
    {
        return $this->errorInfo;
    }
}
