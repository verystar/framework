<?php
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 1/19/16 4:50 PM
 */

namespace Very\Database;

use PDO;

class MysqlConnection extends PDOConnection {

    public function __construct($dsn, $username, $password, $driver_options = array()) {
        $this->connect($dsn, $username, $password, array(PDO::ATTR_CASE => PDO::CASE_LOWER) + $driver_options);
    }
}