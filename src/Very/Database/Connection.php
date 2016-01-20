<?php namespace Very\Database;

use PDO;
use PDOException;
use RuntimeException;

class Connection {
    /**
     * @static
     *
     * @param string $db
     *
     * @return MysqlConnection
     */
    static private $instances = array();

    static function getInstances() {
        return self::$instances;
    }

    /**
     * @param $db
     *
     * @return MysqlConnection
     */
    static public function getInstance($db) {
        if (!isset(self::$instances[$db])) {
            $config = config('db', $db);
            if ($config) {

                try {
                    if ($config['dbtype'] == 'mysql') {
                        $dns = 'mysql:host=' . $config['dbhost'] . ';dbname=' . $config['dbname'];

                        if (isset($config['dbport']) && !empty($config['dbport'])) {
                            $dns .= ';port=' . $config['dbport'];
                        }

                        self::$instances[$db] = new MysqlConnection($dns, $config['dbuser'], $config['dbpswd'], array(
                            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $config['dbcharset']));
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