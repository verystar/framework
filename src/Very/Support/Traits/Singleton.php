<?php
namespace Very\Support\Traits;

/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 6/3/16 21:58
 * The singleton trait.
 *
 * @see   http://en.wikipedia.org/wiki/Singleton_pattern
 * @since 1.0.0
 */
trait Singleton
{
    /**
     * Returns the only instance of the Singleton class.
     *
     * @return static the only instance of the Singleton class.
     */
    public static function getInstance()
    {
        static $_instance = null;

        return $_instance ? $_instance : $_instance = new static();
    }
}