<?php
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 1/19/16 4:50 PM.
 */

namespace Very\Database;

use PDO;

class MysqlConnection extends PDOConnection
{
    public function __construct(array $config)
    {
        $options = isset($config['options']) ? $config['options'] : [];
        $this->connect($this->getDsn($config), $config['dbuser'], $config['dbpswd'], $options);
        $this->configureEncoding($this->getPdo(), $config);
        $this->configureTimezone($this->getPdo(), $config);
        $this->setModes($this->getPdo(), $config);
    }

    /**
     * Set the connection character set and collation.
     *
     * @param  \PDO  $connection
     * @param  array $config
     *
     * @return mixed
     */
    protected function configureEncoding($connection, array $config)
    {
        if (!isset($config['charset'])) {
            return $connection;
        }

        $connection->prepare(
            "set names '{$config['charset']}'" . $this->getCollation($config)
        )->execute();
    }

    /**
     * Set the timezone on the connection.
     *
     * @param  \PDO  $connection
     * @param  array $config
     *
     * @return void
     */
    protected function configureTimezone($connection, array $config)
    {
        if (isset($config['timezone'])) {
            $connection->prepare('set time_zone="' . $config['timezone'] . '"')->execute();
        }
    }

    /**
     * Get the collation for the configuration.
     *
     * @param  array $config
     *
     * @return string
     */
    protected function getCollation(array $config)
    {
        return !is_null($config['collation']) ? " collate '{$config['collation']}'" : '';
    }


    /**
     * Create a DSN string from a configuration.
     *
     * Chooses socket or host/port based on the 'unix_socket' config value.
     *
     * @param  array $config
     *
     * @return string
     */
    protected function getDsn(array $config)
    {
        return $this->hasSocket($config)
            ? $this->getSocketDsn($config)
            : $this->getHostDsn($config);
    }


    /**
     * Determine if the given configuration array has a UNIX socket value.
     *
     * @param  array $config
     *
     * @return bool
     */
    protected function hasSocket(array $config)
    {
        return isset($config['unix_socket']) && !empty($config['unix_socket']);
    }

    /**
     * Get the DSN string for a socket configuration.
     *
     * @param  array $config
     *
     * @return string
     */
    protected function getSocketDsn(array $config)
    {
        return "mysql:unix_socket={$config['unix_socket']};dbname={$config['database']}";
    }

    /**
     * Get the DSN string for a host / port configuration.
     *
     * @param  array $config
     *
     * @return string
     */
    protected function getHostDsn(array $config)
    {
        $dns = 'mysql:host=' . $config['dbhost'] . ';dbname=' . $config['dbname'];
        if (isset($config['dbport']) && !empty($config['dbport'])) {
            $dns .= ';port=' . $config['dbport'];
        }
        return $dns;
    }

    /**
     * Set the modes for the connection.
     *
     * @param  \PDO  $connection
     * @param  array $config
     *
     * @return void
     */
    protected function setModes(PDO $connection, array $config)
    {
        if (isset($config['modes'])) {
            $this->setCustomModes($connection, $config);
        } elseif (isset($config['strict'])) {
            if ($config['strict']) {
                $connection->prepare($this->strictMode())->execute();
            } else {
                $connection->prepare("set session sql_mode='NO_ENGINE_SUBSTITUTION'")->execute();
            }
        }
    }


    /**
     * Set the custom modes on the connection.
     *
     * @param  \PDO  $connection
     * @param  array $config
     *
     * @return void
     */
    protected function setCustomModes(PDO $connection, array $config)
    {
        $modes = implode(',', $config['modes']);

        $connection->prepare("set session sql_mode='{$modes}'")->execute();
    }

    /**
     * Get the query to enable strict mode.
     *
     * @return string
     */
    protected function strictMode()
    {
        return "set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'";
    }
}
