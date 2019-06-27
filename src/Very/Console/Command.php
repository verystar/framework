<?php
namespace Very\Console;

/**
 * Created by PhpStorm.
 * User: fifsky
 * Date: 13/01/2017 6:24 PM
 */
class Command
{
    public function option($name = '', $default = null)
    {
        static $argv = null;
        if (is_null($argv)) {
            $argv = $this->parse();
        }

        if ('' == $name) {
            return $argv;
        }
        return $argv[$name] ? $argv[$name] : $default;
    }

    private function parse()
    {
        if (empty($_SERVER['argv'])) {
            return [];
        }
        $opts = [];
        foreach ($_SERVER['argv'] as $argv) {
            if (preg_match('/^\-\-([\w\-]+)=(.*)/', $argv, $matches)) {
                $opts[$matches[1]] = isset($matches[2]) ? $matches[2] : null;
            } elseif (preg_match('/^\-([a-z])(.*)/', $argv, $matches)) {
                $opts[$matches[1]] = isset($matches[2]) ? $matches[2] : null;
            }
        }
        return $opts;
    }
}