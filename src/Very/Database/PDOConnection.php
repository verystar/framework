<?php
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 1/19/16 4:51 PM.
 */

namespace Very\Database;

use PDO;
use Closure;
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
        PDO::ATTR_CASE              => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_AUTOCOMMIT        => true,
        PDO::ATTR_ORACLE_NULLS      => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES  => false,
    ];

    /**
     * The default fetch mode of the connection.
     *
     * @var int
     */
    protected $fetchMode = PDO::FETCH_ASSOC;

    /**
     * If lost conntection reconnect
     * @var callable
     */
    protected $reconnector;

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
     * Indicates if the connection is in a "dry run".
     *
     * @var bool
     */
    protected $pretending = false;


    /**
     * @var \PDO
     */
    protected $pdo;

    protected $forcePrefix = "";

    public function forceMaster()
    {
        $this->forcePrefix = "/*TDDL:MASTER*/";
    }

    public function forceSlave()
    {
        $this->forcePrefix = "/*TDDL:SLAVE*/";
    }

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
                if ($this->causedByLostConnection($e)) {
                    $this->pdo = $this->createPdoConnection(
                        $dsn, $username, $password, $this->options
                    );
                } else {
                    throw $e;
                }
            }

            $connection_time = $this->getElapsedTime($start_time);
            //监控SQL连接效率
            if ($connection_time) {
                mstat()->set(1, 'SQL连接效率', mstat()->formatTime($connection_time), $dsn);
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
     * @return mixed
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
            'Transaction() on null',
            'child connection forced to terminate due to client_idle_limit',
            'query_wait_timeout',
            'reset by peer',
        ]);
    }

    /**
     * Handle a query exception that occurred during query execution.
     *
     * @param  \Very\Database\QueryException $e
     * @param  string                        $query
     * @param  array                         $bindings
     * @param  \Closure                      $callback
     *
     * @return mixed
     *
     * @throws \Very\Database\QueryException
     */
    protected function tryAgainIfCausedByLostConnection(QueryException $e, $query, $bindings, Closure $callback)
    {
        if ($this->causedByLostConnection($e->getPrevious())) {
            $this->reconnect();

            return $this->runQueryCallback($query, $bindings, $callback);
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
     * @param       $page
     * @param int   $num
     * @param array $params
     * @param bool  $next_page
     *
     * @return array
     */
    public function getLimit($sql, $page, $num = 0, $params = array(), $next_page = false)
    {
        $num = (int)$num;
        $sql .= ' limit :limit_from,:limit';

        if ($next_page && $num) {
            $limit_from = ($page - 1) * ($num - 1);
        } else {
            $limit_from = ($page - 1) * $num;
        }

        $ret = $this->getAll($sql, array_merge($params, array('limit' => $num, 'limit_from' => $limit_from)));
        return $ret;
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
        $ret   = $this->getLimit($sql, $page, $num + 1, $params, true);
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
     * Reconnect to the database if a PDO connection is missing.
     *
     * @return void
     */
    protected function reconnectIfMissingConnection()
    {
        if (is_null($this->pdo)) {
            $this->reconnect();
        }
    }


    /**
     * Run a SQL statement.
     *
     * @param  string   $query
     * @param  array    $bindings
     * @param  \Closure $callback
     *
     * @return mixed
     *
     * @throws \Very\Database\QueryException
     */
    protected function runQueryCallback($query, $bindings, Closure $callback)
    {
        // To execute the statement, we'll simply call the callback, which will actually
        // run the SQL against the PDO connection. Then we can calculate the time it
        // took to execute and log the query SQL, bindings and time in our memory.
        try {
            $result = $callback($query, $bindings);
        }

            // If an exception occurs when attempting to run a query, we'll format the error
            // message to include the bindings with SQL, which will make this exception a
            // lot more helpful to the developer instead of just the database's errors.
        catch (Exception $e) {
            throw new QueryException(
                $query, $bindings, $e
            );
        }

        return $result;
    }


    /**
     * Handle a query exception.
     *
     * @param  \Very\Database\QueryException $e
     * @param  string                        $query
     * @param  array                         $bindings
     * @param  \Closure                      $callback
     *
     * @return mixed
     */
    protected function handleQueryException($e, $query, $bindings, Closure $callback)
    {
        return $this->tryAgainIfCausedByLostConnection(
            $e, $query, $bindings, $callback
        );
    }


    /**
     * Run a SQL statement and log its execution context.
     *
     * @param  string   $query
     * @param  array    $bindings
     * @param  \Closure $callback
     *
     * @return mixed
     *
     * @throws \Very\Database\QueryException
     */
    protected function run($query, $bindings, Closure $callback)
    {
        $this->reconnectIfMissingConnection();

        $start = microtime(true);

        // Here we will run this query. If an exception occurs we'll determine if it was
        // caused by a connection that has been lost. If that is the cause, we'll try
        // to re-establish connection and re-run the query with a fresh connection.
        try {
            $result = $this->runQueryCallback($query, $bindings, $callback);
        } catch (QueryException $e) {
            $result = $this->handleQueryException(
                $e, $query, $bindings, $callback
            );
            logger()->error('SQL Error', [$query, $bindings]);
            mstat()->set(1, 'BUG错误', 'SQL执行错误', $query, $e->getMessage(), 100);
        }

        $execute_time = $this->getElapsedTime($start);
        //监控SQL执行效率
        if ($execute_time) {
            mstat()->set(1, 'SQL执行效率', mstat()->formatTime($execute_time), $query);
        }

        // Once we have run the query we will calculate the time that it took to run and
        // then log the query, bindings, and execution time so we will report them on
        // the event that the developer needs them. We'll log time in milliseconds.
        $this->logQuery(
            $query, $bindings, $execute_time
        );

        return $result;
    }


    /**
     * Determine if the connection in a "dry run".
     *
     * @return bool
     */
    public function pretending()
    {
        return $this->pretending === true;
    }


    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  string $query
     * @param  array  $bindings
     *
     * @return \PDOStatement
     */
    public function execute($query, $bindings = [])
    {
        if (strtolower(substr($query, 0, 6)) === "select" && $this->forcePrefix) {
            $query = $this->forcePrefix . $query;
        }

        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return true;
            }

            $statement = $this->getPdo()->prepare($query);

            $this->bindValues($statement, $bindings);

            $statement->execute();
            return $statement;
        });
    }

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param  string $query
     * @param  array  $bindings
     *
     * @return int
     */
    public function affectingStatement($query, $bindings = [])
    {
        $statement = $this->execute($query, $bindings);
        return $statement->rowCount();
    }

    /**
     * Log a query in the connection's query log.
     *
     * @param  string     $query
     * @param  array      $bindings
     * @param  float|null $time
     *
     * @return void
     */
    public function logQuery($query, $bindings, $time = null)
    {
        if ($this->loggingQueries) {
            $this->queryLog[] = compact('query', 'bindings', 'time');
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
     * @return void
     */
    public function enableQueryLog()
    {
        $this->loggingQueries = true;
    }

    /**
     * Disable the query log on the connection.
     *
     * @return void
     */
    public function disableQueryLog()
    {
        $this->loggingQueries = false;
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
     * @param  float $start
     *
     * @return float
     */
    protected function getElapsedTime($start)
    {
        return round(microtime(true) - $start, 6);
    }

    public function update($table, $where, $bindings = [], $where_field = [])
    {
        $sets = array();
        foreach ($bindings as $k => $v) {
            if (!in_array($k, $where_field)) {
                $sets[] = ' `' . $k . '` =:' . $k;
            }
        }

        $set   = implode(',', $sets);
        $query = "update {$table} set $set where $where";
        return $this->affectingStatement($query, $bindings);
    }

    public function insert($table, $bindings = [])
    {
        if (!$bindings) {
            return false;
        }

        $keys         = array_keys($bindings);
        $fileds       = '`' . implode('`,`', $keys) . '`';
        $filed_values = ':' . implode(',:', $keys);

        $query     = "insert into {$table}($fileds) values($filed_values)";
        $row_count = $this->affectingStatement($query, $bindings);
        if ($row_count) {
            return $this->getPdo()->lastInsertId();
        } else {
            return false;
        }
    }

    public function delete($table, $where, $bindings)
    {
        if (!$bindings) {
            return false;
        }

        $query = "delete from {$table} where $where";
        return $this->affectingStatement($query, $bindings);
    }
}