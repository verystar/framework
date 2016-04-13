<?php
function module($__module_name__, $__params__ = array()) {
    if ($__params__) {
        extract($__params__);
    }

    $__module_ret__ = NULL;
    if (file_exists(app('path.modules') . $__module_name__ . '.php')) {
        $__module_ret__ = include app('path.modules') . $__module_name__ . '.php';
    } else {
        throw new \RuntimeException('Not found Module file in: ' . app('path.modules') . $__module_name__ . '.php');
    }
    return $__module_ret__;
}

function site_url($var = NULL) {
    if (substr($var, 0, 4) === 'http') {
        if (defined('ENVIRON') && ENVIRON === 'dev') {
            $var = str_replace('//', '//' . ENVIRON . '.', $var);
        }
        return $var;
    } else {
        $site_root = 'http://' . $_SERVER['HTTP_HOST'] . '/';

        if ($var == NULL) {
            return $site_root;
        } else {
            $var = ltrim($var, '/');
            return $site_root . $var;
        }
    }
}

function resource_url($var = NULL, $url_type = 'resource_url') {
    $site_root = config('app', $url_type);
    if ($var == NULL) {
        return $site_root;
    } else {
        $var = ltrim($var, '/');
        $ext = pathinfo($var, PATHINFO_EXTENSION);
        switch ($ext) {
            case 'js':
                $v = '?v=' . config('app', 'js_version', '20121024');
                break;
            case 'css':
                $v = '?v=' . config('app', 'css_version', '20121024');
                break;
            default:
                $v = '';
                break;
        }
        return $site_root . $var . $v;
    }
}

//获取request_uri
function request_uri() {
    if (isset($_SERVER['REQUEST_URI'])) {
        $uri = $_SERVER['REQUEST_URI'];
    } else {
        if (isset($_SERVER['argv'])) {
            $uri = $_SERVER['PHP_SELF'] . '?' . $_SERVER['argv'][0];
        } else {
            $uri = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
        }
    }
    return $uri;
}

/**
 * 构建URL
 *
 * @param array $add_params 添加的参数
 * @param array $del_params 删除的参数
 *
 * @return string
 */
function make_url($add_params = [], $del_params = []) {
    $uri   = request_uri();
    $de    = strpos('?', $uri) === false ? '?' : '&';
    $query = parse_url($uri, PHP_URL_QUERY);

    parse_str($query, $params);
    $params = array_merge($params, $add_params);
    foreach ($params as $k => $v) {
        if (in_array($k, $del_params)) {
            unset($params[$k]);
        }
    }
    $uri = parse_url($uri, PHP_URL_PATH) . ($params ? $de . http_build_query($params) : '');
    return site_url($uri);
}

//判断是否为某个页面
function is_page($controller, $action = NULL) {
    $request    = request();
    $has        = false;
    $controller = is_array($controller) ? $controller : [$controller];

    if (in_array($request->getControllerName(), $controller)) {
        $has = true;
        if ($action !== NULL) {
            $action = is_array($action) ? $action : [$action];
            if (in_array($request->getActionName(), $action)) {
                $has = true;
            } else {
                $has = false;
            }
        }
    }

    unset($request);
    return $has;
}

//xml转换为array
function xml_to_array($xml) {
    return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
}

/**
 * 调试函数一律禁止在线上输出
 * @return bool
 */
function e() {
    if (!defined('DEBUG') || !DEBUG) {
        return false;
    }

    $params = func_get_args();
    foreach ($params as $value) {
        if (is_array($value) || is_object($value)) {
            if (isCli()) {
                print_r($value);
                echo "\n";
            } else {
                print_r($value);
                echo '<br/>';
            }
        } else {
            if (isCli()) {
                echo $value, "\n";
            } else {
                echo $value, '<br/>';
            }
        }
    }
}

function return_ip() {
    $ip = "-1";
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_a = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        for ($i = 0; $i < count($ip_a); $i++) { //
            $tmp = trim($ip_a[$i]);
            if ($tmp == 'unknown' || $tmp == '127.0.0.1' || strncmp($tmp, '10.', 3) == 0 || strncmp($tmp, '172', 3) == 0 || strncmp($tmp, '192', 3) == 0)
                continue;
            $ip = $tmp;
            break;
        }
    } else if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = trim($_SERVER['HTTP_CLIENT_IP']);
    } else if (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = trim($_SERVER['REMOTE_ADDR']);
    } else {
        $ip = "-1";
    }
    return $ip;
}

function isCli() {
    return PHP_SAPI == 'cli' && empty($_SERVER['REMOTE_ADDR']);
}

/**
 * 调试函数一律禁止在线上输出
 *
 * @param           $info
 * @param bool|true $exit
 *
 * @return bool
 */
function p($info, $exit = true) {
    if (!defined('DEBUG') || !DEBUG) {
        return false;
    }

    $debug  = debug_backtrace();
    $output = '';

    if (isCli()) {
        foreach ($debug as $v) {
            $output .= 'File:' . $v['file'];
            $output .= 'Line:' . $v['line'];
            $output .= $v['class'] . $v['type'] . $v['function'] . '(\'';
            foreach ($v['args'] as $k => $argv) {
                if (is_object($argv)) {
                    $v['args'][$k] = 'Object[' . get_class($argv) . ']';
                }
            }
            $output .= implode('\',\' ', $v['args']);
            $output .= '\')' . PHP_EOL;
        }
        $output .= '[Info]' . PHP_EOL;
        $output .= var_export($info, true) . PHP_EOL;
    } else {
        foreach ($debug as $v) {
            $output .= '<b>File</b>:' . $v['file'] . '&nbsp;';
            $output .= '<b>Line</b>:' . $v['line'] . '&nbsp;';
            $output .= $v['class'] . $v['type'] . $v['function'] . '(\'';

            foreach ($v['args'] as $k => $argv) {
                if (is_object($argv)) {
                    $v['args'][$k] = 'Object[' . get_class($argv) . ']';
                }
            }
            $output .= implode('\',\' ', $v['args']);

            $output .= '\')<br/>';
        }
        $output .= '<b>Info</b>:<br/>';
        $output .= '<pre>';
        $output .= var_export($info, true);
        $output .= '</pre>';
    }

    echo $output;
    if ($exit)
        exit;
}


/**
 * 字符串截取，支持中文和其他编码
 *
 * @param string $str     需要转换的字符串
 * @param int    $start   开始位置
 * @param int    $length  截取长度
 * @param string $charset 编码格式
 * @param string $suffix  截断显示字符
 *
 * @return string
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = '...') {
    if (function_exists("mb_substr"))
        $slice = mb_substr($str, $start, $length, $charset);
    elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
    } else {
        $re['utf-8']  = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = implode("", array_slice($match[0], $start, $length));
    }
    return $slice . $suffix;
}

//去除空格
function trim_space($s) {
    $s = mb_ereg_replace('^(　| )+', '', $s);
    $s = mb_ereg_replace('(　| )+$', '', $s);
    return $s;
}

/**
 * 随机采样
 *
 * @param $str
 * @param $prob
 *
 * @return string
 */
function rand_sample($str, $prob = 100) {
    $prob = $prob < 10 ? 10 : $prob;
    $rt   = mt_rand(1, $prob);
    return $rt == 8 ? $str : NULL;
}

//去除空格判断空
function trim_empty($str, $is_zh = false) {
    $str = $is_zh ? trim_space($str) : trim($str);
    return empty($str);
}

//跳转
function redirect($uri = '/', $method = 'location', $http_response_code = 302) {
    switch ($method) {
        case 'refresh'    :
            header("Refresh:0;url=" . $uri);
            break;
        default            :
            header("Location: " . $uri, TRUE, $http_response_code);
            break;
    }
    exit;
}

/**
 * 判断是否为搜索引擎蜘蛛
 * @return bool
 */
function is_search_bot() {
    $bots       = array(
        'Google' => 'Googlebot',
        'Baidu'  => 'Baiduspider',
        'Yahoo'  => 'Yahoo! Slurp',
        'Soso'   => 'Sosospider',
        'Msn'    => 'msnbot',
        'Sogou'  => 'Sogou spider',
        'Yodao'  => 'YodaoBot'
    );
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    foreach ($bots as $k => $v) {
        if (stristr($v, $user_agent)) {
            return $k;
            break;
        }
    }
    return false;

//    if (in_array($_SERVER['HTTP_USER_AGENT'], array(
//        'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
//        'Sosospider+(+http://help.soso.com/webspider.htm)',
//        'Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)',
//        'Mozilla/5.0 (compatible;YodaoBot-Image/1.0;http://www.youdao.com/help/webmaster/spider/;)',
//    ))){
//        return true;
//    }
//    return false;
}

/**
 * 判断是否为移动设备
 * @return bool
 */
function is_ios() {
    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    $type  = false;
    if (strpos($agent, 'iphone') || strpos($agent, 'ipad') || strpos($agent, 'android')) {
        $type = true;
    }
    return $type;
}

function _iconv(&$data, $key, $encodeing) {
    $data = mb_convert_encoding($data, $encodeing[1], $encodeing[0]);
}

function gbk2utf8($data) {
    if (is_array($data)) {
        array_walk_recursive($data, '_iconv', array('gbk', 'utf-8'));
    } elseif (is_object($data)) {
        array_walk_recursive(get_object_vars($data), '_iconv', array('utf-8', 'gbk'));
    } else {
        $data = mb_convert_encoding($data, 'utf-8', 'gbk');
    }
    return $data;
}

function utf8togbk($data) {
    if (is_array($data)) {
        array_walk_recursive($data, '_iconv', array('utf-8', 'gbk'));
    } elseif (is_object($data)) {
        array_walk_recursive(get_object_vars($data), '_iconv', array('utf-8', 'gbk'));
    } else {
        $data = mb_convert_encoding($data, 'gbk', 'utf-8');
    }
    return $data;
}

/**
 * Set HTTP Status Header
 *
 * @access    public
 *
 * @param    $code int the status code
 * @param    $text string
 *
 * @return    void
 */
function set_status_header($code = 200, $text = '') {
    static $stati = array(
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',

        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',

        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    );

    if (isset($stati[$code])) {

        $text = $text ? $text : $stati[$code];

        $server_protocol = (isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : FALSE;

        if (substr(php_sapi_name(), 0, 3) == 'cgi') {
            header("Status: {$code} {$text}", TRUE);
        } elseif ($server_protocol == 'HTTP/1.1' || $server_protocol == 'HTTP/1.0') {
            header($server_protocol . " {$code} {$text}", TRUE, $code);
        } else {
            header("HTTP/1.1 {$code} {$text}", TRUE, $code);
        }
    }
}

/**
 * 生成字符串
 *
 * @param int $len
 *
 * @return string
 */
function rand_str($len = 5) {
    return substr(str_shuffle('1234567890abcdefghijklmnopqrstuvwzxyABCDEFGHIJKLMNOPQRSTUVWZXY'), 0, $len);
}

function base32_encode($input) {
    $base32_alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $output          = '';
    //$position         = 0;
    $stored_data      = 0;
    $stored_bit_count = 0;
    $index            = 0;

    while ($index < strlen($input)) {
        $stored_data <<= 8;
        $stored_data += ord($input[$index]);
        $stored_bit_count += 8;
        $index += 1;

        //take as much data as possible out of storedData
        while ($stored_bit_count >= 5) {
            $stored_bit_count -= 5;
            $output .= $base32_alphabet[$stored_data >> $stored_bit_count];
            $stored_data &= ((1 << $stored_bit_count) - 1);
        }
    } //while

    //deal with leftover data
    if ($stored_bit_count > 0) {
        $stored_data <<= (5 - $stored_bit_count);
        $output .= $base32_alphabet[$stored_data];
    }
    return $output;
}

/**
 * @param $input
 *
 * @return string
 * @author 蔡旭东 mailto:fifsky@dev.ppstream.com
 */
function base32_decode($input) {
    if (empty($input)) {
        return $input;
    }

    static $asc = array();
    $output = '';
    $v      = 0;
    $vbits  = 0;
    $i      = 0;
    $input  = strtolower($input);
    $j      = strlen($input);
    while ($i < $j) {

        if (!isset($asc[$input[$i]])) {
            $asc[$input[$i]] = ord($input[$i]);
        }

        $v <<= 5;
        if ($input[$i] >= 'a' && $input[$i] <= 'z') {
            $v += ($asc[$input[$i]] - 97);
        } elseif ($input[$i] >= '2' && $input[$i] <= '7') {
            $v += (24 + $input[$i]);
        } else {
            exit(1);
        }
        $i++;

        $vbits += 5;
        while ($vbits >= 8) {
            $vbits -= 8;
            $output .= chr($v >> $vbits);
            $v &= ((1 << $vbits) - 1);
        }
    }
    return $output;
}

if (!function_exists('textarea_to_html')) {
    function textarea_to_html($str) {
        $str = str_replace(chr(13), '<br>', $str);
        $str = str_replace(chr(9), '&nbsp;&nbsp;', $str);
        $str = str_replace(chr(32), '&nbsp;', $str);
        return $str;
    }
}

if (!function_exists('html_to_textarea')) {
    function html_to_textarea($str) {
        $str = str_replace('<br>', chr(13), $str);
        $str = str_replace('&nbsp;', chr(32), $str);
        return $str;
    }
}
//加密解密
function encrypt($string, $skey = '%f1f5kyL@<eYu9n$') {
    $code   = '';
    $key    = substr(md5($skey), 8, 18);
    $keylen = strlen($key);
    $strlen = strlen($string);
    for ($i = 0; $i < $strlen; $i++) {
        $k = $i % $keylen;
        $code .= $string[$i] ^ $key[$k];
    }
    return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($code));
}

function decrypt($string, $skey = '%f1f5kyL@<eYu9n$') {
    $string = base64_decode(str_replace(array('-', '_'), array('+', '/'), $string));
    $code   = '';
    $key    = substr(md5($skey), 8, 18);
    $keylen = strlen($key);
    $strlen = strlen($string);
    for ($i = 0; $i < $strlen; $i++) {
        $k = $i % $keylen;
        $code .= $string[$i] ^ $key[$k];
    }

    return $code;
}


function ifset($array, $key, $default = null) {
    return isset($array[$key]) ? $array[$key] : $default;
}

function show_human_time($timestamp, $format = 'Y-m-d H:i') {
    $time_offset = time() - $timestamp;
    $date_format = date('Y-m-d', $timestamp);
    list($year, $month, $day) = explode("-", $date_format);
    if ($time_offset <= 3600) {
        return ($time_offset <= 0 ? '1' : ceil($time_offset / 60)) . '分钟前';
    } else if ($date_format == date('Y-m-d')) {
        return '今天 ' . date('H:i', $timestamp);
    } else if ($year == date('Y')) {
        return date('m月d日 H:i', $timestamp);
    } else {
        return date($format, $timestamp);
    }
}

function is_utf8($string) {
    //可以使用mb_detect_encoding($string,"UTF-8")替代

    return preg_match('%^(?:
       [\x09\x0A\x0D\x20-\x7E]              # ASCII
       | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
       |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
       | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
       |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
       |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
       | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
       |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
       )*$%xs', $string);
}

function array_get($array, $key, $default = null) {
    if (is_null($key))
        return $array;

    if (isset($array[$key]))
        return $array[$key];

    foreach (explode('.', $key) as $segment) {
        if (!is_array($array) || !array_key_exists($segment, $array)) {
            return $default;
        }

        $array = $array[$segment];
    }

    return $array;
}

function array_set(&$array, $key, $value) {
    if (is_null($key))
        return $array = $value;

    $keys = explode('.', $key);

    while (count($keys) > 1) {
        $key = array_shift($keys);

        // If the key doesn't exist at this depth, we will just create an empty array
        // to hold the next value, allowing us to create the arrays to hold final
        // values at the correct depth. Then we'll keep digging into the array.
        if (!isset($array[$key]) || !is_array($array[$key])) {
            $array[$key] = array();
        }

        $array =& $array[$key];
    }

    $array[array_shift($keys)] = $value;

    return $array;
}

/**
 * 检测参数是否有值，如果有一个参数没有值则返回false
 * @return bool
 */
function required_params() {

    $params = func_get_args();
    foreach ($params as $value) {
        if (is_array($value)) {
            if (empty($value)) {
                return false;
            }
        } else {
            if ($value === NULL || strlen(trim($value)) == 0) {
                return false;
            }
        }
    }
    return true;
}

/**
 * 过滤数组的空
 *
 * @param $arr
 *
 * @return array
 */
function filter_empty($arr) {
    return array_filter($arr, function ($val) {
        if (is_bool($val) || is_array($val)) {
            //此处之所以做出这样的判断因为trim对false 和 true返回值完全不同
            return true;
        }

        return ($val !== '' && $val !== NULL && strlen(trim($val)) > 0);
    });
}

/**
 * 还原过滤的空字段
 *
 * @param $data
 * @param $filed
 *
 * @return array
 */
function restore_empty($data, $filed) {
    return array_merge(array_fill_keys($filed, ''), $data);
}

/**
 * 根据固定key过滤数组
 *
 * @param $data
 * @param $field
 *
 * @return array
 */
function filter_field($data, $field) {
    return array_intersect_key($data, array_fill_keys($field, ''));
}

/**
 * 空字符串转换为NULL，不包含对空格的处理
 *
 * @param $arr
 *
 * @return array
 */
function emptystr_tonull($arr) {
    return array_map(function ($val) {
        if ($val === '') {
            $val = NULL;
        }
        return $val;
    }, $arr);
}


if (!function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @param  string $make
     *
     * @return mixed
     */
    function app($make = null) {
        if (!is_null($make)) {
            return app()->make($make);
        }

        return Very\Application::getInstance();
    }
}


if (!function_exists('view')) {
    /**
     * Get the evaluated view contents for the given view.
     *
     * @param  string $view
     * @param  array  $data
     *
     * @return \Very\View
     */
    function view($view = null, $data = array()) {
        $factory = app('view');

        if (func_num_args() === 0) {
            return $factory;
        }

        return $factory->display($view, $data);
    }
}

if (!function_exists('config')) {
    /**
     * Get / set the specified configuration value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * @param  string $config
     * @param  string $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    function config($config = NULL, $key = null, $default = null) {
        if (is_null($config))
            return app('config');

        if (is_array($key)) {
            return app('config')->set($config, $key);
        }

        return app('config')->get($config, $key, $default);
    }
}

if (!function_exists('logger')) {
    /**
     * Log a debug message to the logs.
     *
     * @param  string $message
     * @param  array  $context
     *
     * @return Very\Logger
     */
    function logger($message = null, array $context = array()) {
        if (is_null($message))
            return app('logger');

        app('logger')->info($message, $context);
    }
}

if (!function_exists('cookie')) {
    /**
     * Create a new cookie instance.
     *
     * @param  string $name
     * @param  string $value
     * @param  int    $time
     * @param  string $path
     * @param  string $domain
     * @param  bool   $secure
     * @param  bool   $httpOnly
     *
     * @return mixed
     */
    function cookie($name = null, $value = null, $time = 86400, $path = '/', $domain = null, $secure = false, $httpOnly = true) {
        /**
         * @var $cookie Very\Http\Cookie
         */
        $cookie = app('cookie');

        if (is_null($name)) {
            return $cookie;
        }

        return $cookie->set($name, $value, $time, $path, $domain);
    }
}


if (!function_exists('session')) {
    /**
     * Get / set the specified session value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * @param  array|string $key
     * @param  mixed        $default
     *
     * @return mixed
     */
    function session($key = null, $default = null) {
        /**
         * @var $session Very\Http\Session
         */
        $session = app('session');

        if (is_null($key))
            return $session;

        if (is_array($key))
            return $session->put($key);

        return $session->get($key, $default);
    }
}

if (!function_exists('request')) {
    /**
     *
     * @return Very\Http\Request
     */
    function request() {
        return app('request');
    }
}

if (!function_exists('response')) {
    /**
     * @return Very\Http\Response
     */
    function response() {
        return app('response');
    }
}

if (!function_exists('helper')) {
    function helper($file_name) {
        $path = app('path.helpers');
        $file = $path . $file_name . '.php';
        static $import_files = [];

        if (!isset($import_files[$file])) {
            if (is_file($file)) {
                require $file;
                $import_files[$file] = 1;
                return true;
            } else {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('model')) {
    /**
     * model加载辅助函数，如model('user')对应的就是加载Model\User
     *
     * @param $model
     *
     * @return mixed
     * @throws Exception
     */
    function model($model) {
        static $instances = array();

        //如果传进来的已经是真是的类名则不处理
        if (isset($instances[$model]) || class_exists($model)) {
            $classname = $model;
        } else {
            $namespace = app('namespace') ? '\\' . app('namespace') : '';
            $classname = implode('/', array_map('ucfirst', explode('/', $model)));
            $classname = $namespace . '\\Models\\' . str_replace("/", "\\", $classname);
        }

        if (!isset($instances[$classname])) {
            if (class_exists($classname)) {
                $instances[$classname] = new $classname;
            } else {
                throw new Exception('Model ' . $classname . ' not found.', Very\Exception::ERR_NOTFOUND_MODEL);
            }
        }
        return $instances[$classname];
    }
}

function debug_start($s) {
    $GLOBALS[$s]['start_time'] = microtime(true);
    if (!isset($GLOBALS[$s]['start_total_time'])) {
        $GLOBALS[$s]['start_total_time'] = $GLOBALS[$s]['start_time'];
    }
    $GLOBALS[$s]['start_mem'] = memory_get_usage();
}

function debug_end($s) {
    $GLOBALS[$s]['end_time'] = microtime(true);
    $GLOBALS[$s]['end_mem']  = memory_get_usage();

    if (isset($GLOBALS[$s]['start_time'])) {
        e($s . ':---Time:' . number_format($GLOBALS[$s]['end_time'] - $GLOBALS[$s]['start_total_time'], 6) . ':---DTime:' . number_format($GLOBALS[$s]['end_time'] - $GLOBALS[$s]['start_time'], 6) . '---Mem:' . number_format(($GLOBALS[$s]['end_mem'] - $GLOBALS[$s]['start_mem']) / (1024 * 1024), 6) . 'M---PMem:' . number_format(memory_get_peak_usage() / (1024 * 1024), 2) . 'M');
    } else {
        e('not start');
    }
}