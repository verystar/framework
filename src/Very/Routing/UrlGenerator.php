<?php

namespace Very\Routing;

class UrlGenerator
{
    private $params = [];
    private $url;
    private $path;
    private $secure;

    public function to($path = null, $params = [], $secure = false)
    {
        $this->make($path, $params, $secure);
        return $this->url();
    }

    /**
     * Make URL
     *
     * @param null  $path
     * @param array $params
     * @param bool  $secure
     *
     * @return $this
     */
    public function make($path = null, $params = [], $secure = false)
    {
        $this->secure = $secure;
        $params_index = strpos($path, '?');
        $query        = $params_index === false ? '' : parse_url($path, PHP_URL_QUERY);
        $this->path   = $params_index === false ? $path : substr($path, 0, $params_index);
        $path_params  = [];
        if ($query) {
            parse_str($query, $path_params);
        }

        $this->params = array_merge($path_params, $params);
        $this->recombine();
        return $this;
    }

    public function url()
    {
        return $this->url;
    }

    /**
     * Recombine url
     */
    private function recombine()
    {
        $params_str = $this->params ? '?' . http_build_query($this->params) : '';
        if (substr($this->path, 0, 4) === 'http') {
            $this->url = $this->path . $params_str;
        } else {
            $site_root = isset($_SERVER['HTTPS']) || $this->secure ? 'https' : 'http';
            $site_root = isset($_SERVER['HTTP_HOST']) ? $site_root . '://' . $_SERVER['HTTP_HOST'] . '/' : '/';

            $this->url = $site_root . ltrim($this->path, '/') . $params_str;
        }
    }

    /**
     * Append url params
     *
     * @param array $params
     *
     * @return mixed
     */
    public function append($params = [])
    {
        $this->params = array_merge($this->params, $params);
        $this->recombine();
        return $this;
    }

    /**
     * Remove url params
     *
     * @param array $params
     *
     * @return mixed
     */
    public function remove($params = [])
    {
        $this->params = array_diff_key($this->params, array_fill_keys($params, ''));
        $this->recombine();
        return $this;
    }
}