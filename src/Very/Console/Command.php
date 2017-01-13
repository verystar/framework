<?php
namespace Very\Console;

/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 13/01/2017 6:24 PM
 */
class Command
{
    public function option($name = '', $default = null)
    {
        static $argv = null;
        if (is_null($argv)) {
            list($shortopts, $longopts) = $this->parse();
            $argv = getopt($shortopts, $longopts);
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
        $opts = ['', []];
        foreach ($_SERVER['argv'] as $argv) {
            if (preg_match('/^\-\-([\w\-]+)/', $argv, $matches)) {
                $opts[1][] = $matches[1] . '::';
            } elseif (preg_match('/^\-([a-z])/', $argv, $matches)) {
                $opts[0] .= $matches[1] . '::';
            }
        }
        return $opts;
    }
}