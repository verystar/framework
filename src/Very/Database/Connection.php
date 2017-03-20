<?php

namespace Very\Database;

use RuntimeException;

class Connection
{
    /**
     * @static
     *
     * @param string $db
     *
     * @return MysqlConnection
     */
    private static $instances = array();

    public static function getInstances()
    {
        return self::$instances;
    }

    /**
     * @param $db
     *
     * @return MysqlConnection
     */
    public static function getInstance($db)
    {
        if (!isset(self::$instances[$db])) {
            $config = config('database.' . $db);
            if (!$config) {
                throw new RuntimeException('Not found database config [' . $db . '], Please checking file config/database.php');
            }

            switch ($config['driver']) {
                case 'mysql':
                    self::$instances[$db] = new MysqlConnection($config);
                    break;
            }
        }

        return self::$instances[$db];
    }
}