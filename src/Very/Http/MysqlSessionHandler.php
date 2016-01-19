<?php namespace Very\Http;
/*
 * Session 存储mysql方案
 * @author caixudong
 *
 * 备注：数据表初始化SQL语句:
 CREATE TABLE IF NOT EXISTS `sessions` (
   `session_id` varchar(32) NOT NULL,
   `active_time` int(12) NOT NULL,
   `content` text NOT NULL,
   PRIMARY KEY (`session_id`)
 ) ENGINE=MEMORY DEFAULT CHARSET=utf8 COMMENT='session表';
 */

class MysqlSessionHandler {

    private static $_db = null;
    private static $_instance = null;

    public function init() {
		//将 session.save_handler 设置为 user，而不是默认的 files
		session_module_name('user');
        session_set_save_handler(
            array('SessionMysql', 'open'),
            array('SessionMysql', 'close'),
            array('SessionMysql', 'read'),
            array('SessionMysql', 'write'),
            array('SessionMysql', 'destory'),
            array('SessionMysql', 'gc')
        );

        $this->_connectMysql();
    }

    public function __destruct() {
        $this->gc();
    }

    public static function getInstance() {
        if (! (self::$_instance instanceof self)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function open($save_path, $session_name) {
        return true;
    }

    public function close() {
        self::$_db = null;
        return true;
    }

    public function read($session_id) {

        $sql = "select content from sessions where session_id='$session_id' limit 1";
        $rs = mysql_query($sql, self::$_db);

        if (mysql_num_rows($rs) != 0) {
            $data = mysql_fetch_row($rs);
            return $data[0];
        }

        return '';
    }

    public function write($session_id, $content) {
        $active_time = time();
        $sql = "insert into sessions(session_id, content, active_time) values('$session_id', '$content', '$active_time')
                on duplicate key update content='$content', active_time='$active_time'";
        return mysql_query($sql, self::$_db);
    }

    public function destory($session_id) {
        $sql = "delete from sessions where session_id='$session_id' limit 1";
        return mysql_query($sql, self::$_db);
    }

    public function gc() {
        $expire_time = time()-SESS_LIFTTIME;
        $sql = "delete from sessions where active_time < '$expire_time'";
        mysql_query($sql, self::$_db);
    }

    private function _connectMysql() {
        global $session_mysql_configs;
        self::$_db = mysql_connect($session_mysql_configs['dbhost'], $session_mysql_configs['dbuser'], $session_mysql_configs['dbpswd']) or die('session db link error!');
        mysql_select_db($session_mysql_configs['dbname']) or die('session db table select error!');
        mysql_query('SET NAMES '.$session_mysql_configs['dbcoding']);
    }

}