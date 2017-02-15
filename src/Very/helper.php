<?php

use Very\Support\Arr;

if (!function_exists('app_path')) {
    /**
     * Get the path to the application folder.
     *
     * @param  string $path
     *
     * @return string
     */
    function app_path($path = '')
    {
        return app('path.app') . ltrim($path, DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('url')) {
    /**
     * Generate a url for the application.
     *
     * @param  string $path
     * @param  mixed  $parameters
     * @param  bool   $secure
     *
     * @return \Very\Routing\UrlGenerator|string
     */
    function url($path = null, $parameters = [], $secure = null)
    {
        if (is_null($path)) {
            return app(\Very\Routing\UrlGenerator::class);
        }

        return app(\Very\Routing\UrlGenerator::class)->to($path, $parameters, $secure);
    }
}

if (!function_exists('resource_url')) {
    /**
     * Generate a resource url for the application.
     *
     * @param null   $var
     * @param string $url_type
     *
     * @return mixed|string|\Very\Config
     */
    function resource_url($var = null, $url_type = 'resource_url')
    {
        $site_root = config('app.' . $url_type);
        if ($var == null) {
            return $site_root;
        } else {
            $var = ltrim($var, '/');
            $ext = pathinfo($var, PATHINFO_EXTENSION);
            switch ($ext) {
                case 'js':
                    $v = '?v=' . config('app.js_version', '20121024');
                    break;
                case 'css':
                    $v = '?v=' . config('app.css_version', '20121024');
                    break;
                default:
                    $v = '';
                    break;
            }
            $resource_path = config('app.' . $url_type . '_path');

            if (is_dir($resource_path)) {
                if (defined('ENVIRON') && ENVIRON === 'local') {
                    $file = rtrim($resource_path, '/') . '/' . $var;
                    if (file_exists($file)) {
                        $v = '?v=' . substr(md5_file($file), 0, 10);
                    }
                } else {
                    $v            = '';
                    $rev_mainfest = rtrim($resource_path, '/') . '/static/rev-manifest.json';
                    static $revs = [];

                    if (file_exists($rev_mainfest)) {
                        if (!$revs) {
                            $revs = json_decode(file_get_contents($rev_mainfest), true);
                        }
                        if ($revs[$var]) {
                            $var = $revs[$var];
                        }
                    }
                }
            }

            return $site_root . $var . $v;
        }
    }
}

if (!function_exists('xml_to_array')) {

    /**
     * xml转换为array
     *
     * @param $xml
     *
     * @return mixed
     */
    function xml_to_array($xml)
    {
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }
}

if (!function_exists('p')) {
    /**
     * 调试函数一律禁止在线上输出
     */
    function p()
    {
        if ((defined('DEBUG') && DEBUG) || is_cli()) {
            $params = func_get_args();
            foreach ($params as $value) {
                if (is_array($value) || is_object($value)) {
                    if (is_cli()) {
                        print_r($value);
                        echo "\n";
                    } else {
                        print_r($value);
                        echo '<br/>';
                    }
                } else {
                    if (is_cli()) {
                        echo $value, "\n";
                    } else {
                        echo $value, '<br/>';
                    }
                }
            }
        }
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML special characters in a string.
     *
     * @param  string $value
     *
     * @return string
     */
    function e($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
    }
}

if (!function_exists('return_ip')) {

    /**
     * get client ip
     * @return string
     */
    function return_ip()
    {
        $ip = '-1';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_a = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            for ($i = 0; $i < count($ip_a); ++$i) { //
                $tmp = trim($ip_a[$i]);
                if ($tmp == 'unknown' || $tmp == '127.0.0.1' || strncmp($tmp, '10.', 3) == 0 || strncmp($tmp, '172', 3) == 0 || strncmp($tmp, '192', 3) == 0) {
                    continue;
                }
                $ip = $tmp;
                break;
            }
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = trim($_SERVER['HTTP_CLIENT_IP']);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = trim($_SERVER['REMOTE_ADDR']);
        } else {
            $ip = '-1';
        }

        return $ip;
    }
}

if (!function_exists('is_cli')) {

    /**
     * Assertions is command line
     * @return bool
     */
    function is_cli()
    {
        return PHP_SAPI == 'cli' && empty($_SERVER['REMOTE_ADDR']);
    }
}

if (!function_exists('dd')) {

    /**
     * debug print and backtrace
     *
     * @param           $info
     * @param bool|true $exit
     *
     * @return bool
     */
    function dd($info, $exit = true)
    {
        if (!defined('DEBUG') || !DEBUG) {
            return false;
        }

        $debug  = debug_backtrace();
        $output = '';

        if (is_cli()) {
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
        if ($exit) {
            exit;
        }
    }
}

if (!function_exists('trim_space')) {

    /**
     * trim space and chinaese space
     *
     * @param $s
     *
     * @return string
     */
    function trim_space($s)
    {
        $s = mb_ereg_replace('^(　| )+', '', $s);
        $s = mb_ereg_replace('(　| )+$', '', $s);

        return $s;
    }
}


if (!function_exists('rand_sample')) {

    /**
     * return rand sample assertion
     *
     * @param $str
     * @param $prob
     *
     * @return string
     */
    function rand_sample($str, $prob = 100)
    {
        $prob = $prob < 10 ? 10 : $prob;
        $rt   = mt_rand(1, $prob);

        return $rt == 8 ? $str : null;
    }
}

if (!function_exists('redirect')) {

    /**
     * Redirect uri
     *
     * @param string $uri
     * @param string $method
     * @param int    $http_response_code
     */
    function redirect($uri = '/', $method = 'location', $http_response_code = 302)
    {
        switch ($method) {
            case 'refresh'    :
                header('Refresh:0;url=' . $uri);
                break;
            default            :
                header('Location: ' . $uri, true, $http_response_code);
                break;
        }
        exit;
    }
}

if (!function_exists('base32_encode')) {

    /**
     * base32 encode
     *
     * @param $input
     *
     * @return string
     */
    function base32_encode($input)
    {
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
}

if (!function_exists('base32_decode')) {

    /**
     * base32 decode
     *
     * @param $input
     *
     * @return string
     */
    function base32_decode($input)
    {
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
            ++$i;

            $vbits += 5;
            while ($vbits >= 8) {
                $vbits -= 8;
                $output .= chr($v >> $vbits);
                $v &= ((1 << $vbits) - 1);
            }
        }

        return $output;
    }
}

if (!function_exists('textarea_to_html')) {
    function textarea_to_html($str)
    {
        $str = str_replace(chr(13), '<br>', $str);
        $str = str_replace(chr(9), '&nbsp;&nbsp;', $str);
        $str = str_replace(chr(32), '&nbsp;', $str);

        return $str;
    }
}

if (!function_exists('html_to_textarea')) {
    function html_to_textarea($str)
    {
        $str = str_replace('<br>', chr(13), $str);
        $str = str_replace('&nbsp;', chr(32), $str);

        return $str;
    }
}
if (!function_exists('encrypt')) {

    /**
     * Simple encryption
     *
     * @param        $string
     * @param string $skey
     *
     * @return mixed
     */
    function encrypt($string, $skey = '%f1f5kyL@<eYu9n$')
    {
        $code   = '';
        $key    = substr(md5($skey), 8, 18);
        $keylen = strlen($key);
        $strlen = strlen($string);
        for ($i = 0; $i < $strlen; ++$i) {
            $k = $i % $keylen;
            $code .= $string[$i] ^ $key[$k];
        }

        return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($code));
    }
}
if (!function_exists('decrypt')) {

    /**
     * Simple encryption
     *
     * @param        $string
     * @param string $skey
     *
     * @return string
     */
    function decrypt($string, $skey = '%f1f5kyL@<eYu9n$')
    {
        $string = base64_decode(str_replace(array('-', '_'), array('+', '/'), $string));
        $code   = '';
        $key    = substr(md5($skey), 8, 18);
        $keylen = strlen($key);
        $strlen = strlen($string);
        for ($i = 0; $i < $strlen; ++$i) {
            $k = $i % $keylen;
            $code .= $string[$i] ^ $key[$k];
        }

        return $code;
    }
}

if (!function_exists('array_get')) {
    /**
     * Get an item from an array using "dot" notation.
     *
     * @param  \ArrayAccess|array $array
     * @param  string             $key
     * @param  mixed              $default
     *
     * @return mixed
     */
    function array_get($array, $key, $default = null)
    {
        return Arr::get($array, $key, $default);
    }
}

if (!function_exists('array_has')) {
    /**
     * Check if an item or items exist in an array using "dot" notation.
     *
     * @param  \ArrayAccess|array $array
     * @param  string|array       $keys
     *
     * @return bool
     */
    function array_has($array, $keys)
    {
        return Arr::has($array, $keys);
    }
}

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param  mixed $value
     *
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (!function_exists('required_params')) {

    /**
     * check params is null first return false
     *
     * @return bool
     */
    function required_params()
    {
        $params = func_get_args();
        foreach ($params as $value) {
            if (is_array($value)) {
                if (empty($value)) {
                    return false;
                }
            } else {
                if ($value === null || strlen(trim($value)) == 0) {
                    return false;
                }
            }
        }

        return true;
    }
}

if (!function_exists('filter_empty')) {

    /**
     * Filter array empty values.
     *
     * @param $arr
     *
     * @return array
     */
    function filter_empty($arr)
    {
        return array_filter($arr, function ($val) {
            if (is_bool($val) || is_array($val)) {
                return true;
            }

            return $val !== '' && $val !== null && strlen(trim($val)) > 0;
        });
    }
}
if (!function_exists('restore_empty')) {

    /**
     * Restore data through the empty keys.
     *
     * @param $data
     * @param $filed
     *
     * @return array
     */
    function restore_empty($data, $filed)
    {
        return array_merge(array_fill_keys($filed, ''), $data);
    }
}

if (!function_exists('filter_field')) {

    /**
     * Data filter through the keys.
     *
     * @param $data
     * @param $field
     *
     * @return array
     */
    function filter_field($data, $field)
    {
        return array_intersect_key($data, array_fill_keys($field, ''));
    }
}

if (!function_exists('emptystr_tonull')) {

    /**
     * empty value to null
     *
     * @param $arr
     *
     * @return array
     */
    function emptystr_tonull($arr)
    {
        return array_map(function ($val) {
            if ($val === '') {
                $val = null;
            }

            return $val;
        }, $arr);
    }
}

if (!function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @param  string $make
     *
     * @return mixed|\Very\Application
     */
    function app($make = null)
    {
        if (is_null($make)) {
            return \Very\Application::getInstance();
        }

        return \Very\Application::getInstance()->make($make);
    }
}

if (!function_exists('view')) {
    /**
     * Get the evaluated view contents for the given view.
     *
     * @param string $view
     * @param array  $data
     *
     * @return \Very\View
     */
    function view($view = null, $data = array())
    {
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
     * @param  array|string $key
     * @param mixed         $default
     *
     * @return mixed | \Very\Config
     */
    function config($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('config');
        }

        if (is_array($key)) {
            return app('config')->set($key);
        }

        return app('config')->get($key, $default);
    }
}

if (!function_exists('logger')) {
    /**
     * Log a debug message to the logs.
     *
     * @param string $message
     * @param array  $context
     *
     * @return \Very\Logger
     */
    function logger($message = null, array $context = array())
    {
        if (is_null($message)) {
            return app('logger');
        }

        app('logger')->info($message, $context);
    }
}

if (!function_exists('cookie')) {
    /**
     * Create a new cookie instance.
     *
     * @param string $name
     * @param string $value
     * @param int    $time
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param bool   $httpOnly
     *
     * @return mixed | Very\Cookie\CookieJar
     */
    function cookie($name = null, $value = null, $time = 86400, $path = '/', $domain = null, $secure = false, $httpOnly = true)
    {
        /**
         * @var $cookie \Very\Cookie\CookieJar
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
     * @param array|string $key
     * @param mixed        $default
     *
     * @return mixed | Very\Session\SessionManager
     */
    function session($key = null, $default = null)
    {
        /**
         * @var $session \Very\Session\SessionManager
         */
        $session = app('session');

        if (is_null($key)) {
            return $session;
        }

        if (is_array($key)) {
            return $session->put($key);
        }

        return $session->get($key, $default);
    }
}

if (!function_exists('request')) {
    /**
     * @return \Very\Http\Request
     */
    function request()
    {
        return app('request');
    }
}

if (!function_exists('router')) {
    /**
     * @return \Very\Routing\Router
     */
    function router()
    {
        return app('router');
    }
}

if (!function_exists('response')) {
    /**
     * @return \Very\Http\Response
     */
    function response()
    {
        return app('response');
    }
}

if (! function_exists('trans')) {
    /**
     * Translate the given message.
     *
     * @param  string  $key
     * @param  array   $replace
     * @param  string  $locale
     * @return \Very\Translation\Translator|string
     */
    function trans($key = null, $replace = [], $locale = null)
    {
        if (is_null($key)) {
            return app('translator');
        }

        return app('translator')->trans($key, $replace, $locale);
    }
}

if (!function_exists('mstat')) {
    /**
     * @return \Very\Support\Stat
     */
    function mstat()
    {
        return app()->make('mstat');
    }
}

if (!function_exists('debug_start')) {
    function debug_start($s)
    {
        $GLOBALS[$s]['start_time'] = microtime(true);
        if (!isset($GLOBALS[$s]['start_total_time'])) {
            $GLOBALS[$s]['start_total_time'] = $GLOBALS[$s]['start_time'];
        }
        $GLOBALS[$s]['start_mem'] = memory_get_usage();
    }
}


if (!function_exists('debug_end')) {
    function debug_end($s)
    {
        $GLOBALS[$s]['end_time'] = microtime(true);
        $GLOBALS[$s]['end_mem']  = memory_get_usage();

        if (isset($GLOBALS[$s]['start_time'])) {
            p($s . ':---Time:' . number_format($GLOBALS[$s]['end_time'] - $GLOBALS[$s]['start_total_time'],
                    6) . ':---DTime:' . number_format($GLOBALS[$s]['end_time'] - $GLOBALS[$s]['start_time'],
                    6) . '---Mem:' . number_format(($GLOBALS[$s]['end_mem'] - $GLOBALS[$s]['start_mem']) / (1024 * 1024),
                    6) . 'M---PMem:' . number_format(memory_get_peak_usage() / (1024 * 1024), 2) . 'M');
        } else {
            p('not start');
        }
    }
}

if (!function_exists('curl')) {
    /**
     * @return \Very\Support\Curl
     */
    function curl()
    {
        return new Very\Support\Curl();
    }
}

if (!function_exists('retry')) {
    /**
     * Retry an operation a given number of times.
     *
     * @param          $times
     * @param callable $callback
     * @param int      $sleep
     *
     * @return mixed
     * @throws Exception
     */
    function retry($times, callable $callback, $sleep = 0)
    {
        $times--;
        beginning:
        try {
            return $callback();
        } catch (Exception $e) {
            if (!$times) {
                throw $e;
            }
            $times--;
            if ($sleep) {
                usleep($sleep * 1000);
            }
            goto beginning;
        }
    }
}