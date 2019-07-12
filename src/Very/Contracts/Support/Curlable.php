<?php

namespace Very\Contracts\Support;


interface Curlable
{

    /**
     * 重试次数 $this->get()->retry(2)
     *
     * @param $num
     *
     * @return $this
     */
    public function retry($num);

    /**
     * 设置SSL文件
     *
     * @param $cert_file
     * @param $key_file
     *
     * @return mixed
     */
    public function setSSLFile($cert_file, $key_file);


    /**
     * get 请求
     *
     * @param $url
     *
     * @return mixed
     */
    public function get($url);

    /**
     * post 请求
     *
     * @param $url
     * @param $data
     *
     * @return mixed
     */
    public function post($url, $data);

    /**
     * delete 请求
     *
     * @param $url
     *
     * @return mixed
     */
    public function delete($url);

    /**
     * put 请求
     *
     * @param $url
     * @param $data
     *
     * @return mixed
     */
    public function put($url, $data);

    /**
     * 并行请求
     *
     * @param $urls
     *
     * @return mixed
     */
    public function multi($urls);

    /**
     * 获取debug信息
     *
     * @return mixed
     */
    public function debug();

    /**
     * 获取curl错误码
     *
     * @return mixed
     */
    public function getError();


    /**
     * 获取curl 错误信息
     * @return mixed
     */
    public function getErrorInfo();

    /**
     * 获取curl请求响应body
     *
     * @return mixed
     */
    public function getBody();

    /**
     * 设置头信息
     *
     * @param $headers
     *
     * @return mixed
     */
    public function setHeader($headers);

    /**
     * 获取响应 header信息
     *
     * @param null $key
     *
     * @return mixed
     */
    public function getHeader($key = null);

    /**
     * 获取响应状态码
     *
     * @return string
     */
    public function getStatusCode();

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
    public function getRequestTime($time_key = 'total_time');

    /**
     * 设置CURLOPT_REFERER
     *
     * @param $url
     *
     * @return $this
     */
    public function setReferer($url);

    /**
     * 设置是否ajax请求
     *
     * @return $this
     */
    public function setAjax();

    /**
     * 设置超时时间
     *
     * @param $second
     *
     * @return $this
     */
    public function setTimeout($second = 5);

    /**
     * 设置是否开启日志
     *
     * @param $is_log
     *
     * @return $this
     */
    public function setLog($is_log);

    /**
     * 设置是否json格式提交信息
     *
     * @return $this
     */
    public function setPostUsingJson();

}