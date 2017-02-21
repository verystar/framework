<?php

namespace Very\Database;

use PDO;
use PDOException;
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
            $config = config('db.' . $db);
            if ($config) {
                try {
                    if ($config['dbtype'] == 'mysql') {
                        $dns = 'mysql:host=' . $config['dbhost'] . ';dbname=' . $config['dbname'];

                        if (isset($config['dbport']) && !empty($config['dbport'])) {
                            $dns .= ';port=' . $config['dbport'];
                        }

                        self::$instances[$db] = new MysqlConnection($dns, $config['dbuser'], $config['dbpswd']);
                        $collation = !is_null($config['collation']) ? " collate '{$config['collation']}'" : '';

                        self::$instances[$db]->prepare(
                            "set names '{$config['charset']}'" . $collation
                        )->execute();
                    }

                    return self::$instances[$db];
                } catch (PDOException $e) {
                    throw new RuntimeException("DB connect error for db $db:" . $e->getMessage());
                }
            } else {
                throw new RuntimeException('DB config error! Please checking dir in config/db.php');
            }
        } else {
            return self::$instances[$db];
        }
    }
}