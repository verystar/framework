<?php
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 4/13/16 21:30.
 */

namespace Very\Hashing;

class Password
{
    /**
     * 生成密码
     *
     * @param       $password
     * @param int   $algo
     * @param array $options
     *
     * @return bool|string
     */
    public static function make($password, $algo = PASSWORD_DEFAULT, array $options = array())
    {
        return password_hash($password, $algo, $options);
    }

    /**
     * 获取信息.
     *
     * @param $hash
     *
     * @return array
     */
    public static function getInfo($hash)
    {
        return password_get_info($hash);
    }

    /**
     * 检测新的散列算法.
     *
     * @param       $hash
     * @param int   $algo
     * @param array $options
     *
     * @return string
     */
    public static function rehash($hash, $algo = PASSWORD_DEFAULT, array $options = array())
    {
        return password_needs_rehash($hash, $algo, $options);
    }

    /**
     * 验证密码
     *
     * @param $password
     * @param $hash
     *
     * @return bool
     */
    public static function verify($password, $hash)
    {
        return password_verify($password, $hash);
    }
}
