<?php

/**
 * Created by PhpStorm.
 * User: 蔡旭东 fifsky@gmail.com
 * Date: 14-7-21
 * Time: 下午2:18.
 */

namespace Very\Support;

use Very\Contracts\Support\Curlable;

class Curl implements Curlable {

    private $ch;
    private $timeout = 5;
    private $is_ajax = false;
    //@deprecated
    private $is_stat = true; //是否监控统计
    private $referer = NULL;
    private $curl_error;
    private $error_info;
    private $body;
    private $header;
    private $is_log = false;
    private $http_header = [];
    private $url;
    private $method;
    private $post_data;
    private $post_using_json = false;

    /**
     * Verify SSL Cert.
     * @ignore
     */
    private $ssl_verifypeer = false;
    private $ssl_cert_file = '';
    private $ssl_key_file = '';
    private $ssl_version = '';

    /**
     * @param        $url
     * @param string $method
     * @param string $post_data
     *
     * @return $this
     */
    private function exec($url, $method = 'GET', $post_data = '')
    {

        if (!$url) {
            throw new \RuntimeException('CURL url is null:' . __FILE__);
        }
        $this->ch = curl_init();
        $this->defaultOptions($this->ch, $url);
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $method);
        $is_file    = false;
        $encode_arr = [];
        $user_agent_params = [];
        if ($post_data) {
            if (is_array($post_data) && !$this->post_using_json) {
                // 统一 user_agent 信息 start:
                if (isset($post_data['http_user_agent_params'])) {
                    $user_agent_params = (array)$post_data['http_user_agent_params'];
                    unset($post_data['http_user_agent_params']);
                }
                // end;

                foreach ($post_data as $k => $v) {
                    if ("@" != substr($v, 0, 1)) //判断是不是文件上传
                    {
                        $encode_arr[$k] = $v;
                    } else //文件上传用multipart/form-data，否则用www-form-urlencoded
                    {
                        $is_file        = true;
                        $encode_arr[$k] = new \CURLFile(substr($v, 1));
                    }
                }
            }

            if ($is_file) {
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, $encode_arr);
            } elseif ($this->post_using_json) {
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($post_data));
            }  else {
                $post_data = is_array($post_data) ? http_build_query($post_data) : $post_data;
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_data);
            }
        }

        // 统一 user_agent 信息 start:
        if (strpos($url, '.verystar.cn')!==false || strpos($url, '.verystar.net') !== false) {
            array_unshift($user_agent_params, 'php');
            // 用户访问代理 User-Agent
            curl_setopt($this->ch, CURLOPT_USERAGENT, 'VeryPay/1.0 (' . implode('; ', $user_agent_params) . ')');
        }
        // end

        if ($this->http_header) {
//            $this->http_header = array(
//                'Content-Length: ' . strlen($post_data),
//                'Content-Type: ' . $content_type,
//                'Accept: ' . $accept_type
//            );
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->http_header);
        }
        $this->body       = curl_exec($this->ch);
        $this->curl_error = curl_errno($this->ch);
        $this->header     = curl_getinfo($this->ch);
        $this->error_info = curl_error($this->ch);
        $this->log($url);
        if (is_resource($this->ch)) {
            curl_close($this->ch);
        }

        $this->url       = $url;
        $this->method    = $method;
        $this->post_data = $post_data;
        return $this;

    }

    /**
     * 重试次数 $this->get()->retry(2)
     *
     * @param $num
     *
     * @return $this
     */
    public function retry($num) {
        if ($this->getError()) {
            while (true) {
                if (!$num) {
                    break;
                }

                $this->exec($this->url, $this->method, $this->post_data);

                if (!$this->getError()) {
                    break;
                }
                $num--;
            }
        }

        return $this;
    }

    public function setSSLFile($cert_file, $key_file) {

        $this->ssl_verifypeer = true;
        if (is_file($cert_file)) {
            $this->ssl_cert_file = $cert_file;
        }

        if (is_file($key_file)) {
            $this->ssl_key_file = $key_file;
        }
    }

    //网页内容抓取
    public function get($url) {
        return $this->exec($url);
    }

    //监控统计
    private function log($url)
    {
        if ($this->is_log) {
            //记录LOG
            $log = logger();
            $log_arr = array(
                'URL' => $url,
                'HC' => $this->getStatusCode(), // http_code
                'RT' => $this->getRequestTime(), //request_time
                'EN' => $this->getError(), //errno
            );

            if ($this->getError()) {
                $log->error(json_encode($log_arr));
            } else {
                $log->debug(json_encode($log_arr));
            }
        }
    }

    public function debug() {
        return [
            'URL'    => $this->url,
            '状态'     => $this->getStatusCode(), // http_code
            '请求耗时'   => $this->getRequestTime(), //request_time
            '错误码'    => $this->getError(), //errno
            'Header' => $this->getHeader(), //header
            'ErrInfo'=> $this->getErrorInfo()//error info
        ];
    }

    //curl Post数据
    public function post($url, $data) {
        return $this->exec($url, 'POST', $data);
    }

    //REST DELETE
    public function delete($url) {
        return $this->exec($url, 'DELETE');
    }

    //REST PUT
    public function put($url, $data) {
        return $this->exec($url, 'PUT', $data);
    }

    public function multi($urls, $method = 'GET') {
        $mh = curl_multi_init();

        $conn = $contents = [];
        foreach ($urls as $i => $val) {
            if (is_array($val)) {
                $url       = $val['url'];
                $post_data = isset($val['post_data']) ? $val['post_data'] : '';
            }else{
                $url = $val;
                $post_data = '';
            }

            $conn[$i] = curl_init($url);
            $this->defaultOptions($conn[$i], $url);
            curl_multi_add_handle($mh, $conn[$i]);
            curl_setopt($conn[$i], CURLOPT_CUSTOMREQUEST, $method);
            if ($post_data) {
                $post_data = is_array($post_data) ? http_build_query($post_data) : $post_data;
                curl_setopt($conn[$i], CURLOPT_POSTFIELDS, $post_data);

                if ($post_data{0} == "{") {
                    curl_setopt($conn[$i], CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
                }
            }
        } // 初始化  

        do {
            curl_multi_exec($mh, $active);
        } while ($active); // 执行  

        foreach ($urls as $i => $url) {
            $contents[$i] = curl_multi_getcontent($conn[$i]);
            curl_multi_remove_handle($mh, $conn[$i]);
            curl_close($conn[$i]);
        } // 结束清理  
        curl_multi_close($mh);
        return $contents;
    }


    private function defaultOptions(&$ch, $url) {
        $userAgent = 'Mozilla/4.0+(compatible;+MSIE+6.0;+Windows+NT+5.1;+SV1)';

        if ($this->referer = NULL) {
            curl_setopt($ch, CURLOPT_REFERER, $this->referer); //设置 referer
        }
        curl_setopt($ch, CURLOPT_URL, $url); //设置访问的url地址
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout); //设置超时
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent); //用户访问代理 User-Agent
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0); //跟踪301
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //返回结果
        if (substr($url, 0, 5) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }

        if ($this->is_ajax) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Requested-With: XMLHttpRequest", "X-Prototype-Version:1.5.0"));
        }

        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        if ($this->ssl_verifypeer && $this->ssl_cert_file && $this->ssl_key_file) {
            //还原
            $this->ssl_verifypeer = false;
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            //默认格式为PEM，可以注释
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, $this->ssl_cert_file);
            //默认格式为PEM，可以注释
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLKEY, $this->ssl_key_file);
        }

        if(!empty($this->ssl_version)) {
            $version = $this->ssl_version;
            curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, $version);
            $this->ssl_version = '';
        }
    }

    public function getError() {
        return $this->curl_error;
    }

    public function getErrorInfo(){
        return $this->error_info;
    }

    public function getBody() {
        return $this->body;
    }

    public function setSslVersion($version) {
        return $this->ssl_version = $version;
    }

    public function setHeader($headers) {
        $this->http_header = $headers;
    }

    public function getHeader($key = NULL) {
        if ($key !== NULL) {
            return isset($this->header[$key]) ? $this->header : NULL;
        } else {
            return $this->header;
        }
    }

    public function getStatusCode() {
        return $this->header['http_code'];
    }

    /**
     * namelookup_time：DNS 解析域名的时间
     * connect_time：连接时间,从开始到建立TCP连接完成所用时间,包括前边DNS解析时间，如果需要单纯的得到连接时间，用这个time_connect时间减去前边time_namelookup时间。
     * pretransfer_time：从开始到准备传输的时间。
     * time_commect：client和server端建立TCP 连接的时间 里面包括DNS解析的时间
     * starttransfer_time：从client发出请求；到web的server 响应第一个字节的时间 包括前面的2个时间
     * redirect_time：重定向时间，包括到最后一次传输前的几次重定向的DNS解析，连接，预传输，传输时间。
     * total_time：总时间
     *
     * @param $time_key
     *
     * @return mixed
     */
    public function getRequestTime($time_key = 'total_time') {
        return $this->header[$time_key];
    }

    public function setReferer($url) {
        $this->referer = $url;
        return $this;
    }

    public function setAjax() {
        $this->is_ajax = true;
        return $this;
    }

    public function setStat($is_stat) {
        $this->is_stat = $is_stat;
        return $this;
    }

    public function setTimeout($second = 5) {
        $this->timeout = $second;
        return $this;
    }

    public function setLog($is_log) {
        $this->is_log = $is_log;
        return $this;
    }

    public function setPostUsingJson()
    {
        $this->post_using_json = true;
        return $this;
    }
}