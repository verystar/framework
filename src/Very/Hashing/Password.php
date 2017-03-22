<?php
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 4/13/16 21:30.
 */

namespace Very\Hashing;

use RuntimeException;

class Password
{
    /**
     * 生成密码
     *
     * @param       $value
     * @param array $options
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public static function make($value, array $options = array())
    {
        $cost = isset($options['rounds']) ? $options['rounds'] : 10;

        $hash = password_hash($value, PASSWORD_BCRYPT, ['cost' => $cost]);

        if ($hash === false) {
            throw new RuntimeException('Bcrypt hashing not supported.');
        }

        return $hash;
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
     * @param array $options
     *
     * @return string
     */
    public static function rehash($hash, array $options = array())
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, [
            'cost' => isset($options['rounds']) ? $options['rounds'] : 10,
        ]);
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
