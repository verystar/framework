<?php

namespace Very\Encryption;

/**
 * 纯数字加密算法
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 15/9/30 下午2:31
 * $dn = new DEncrypt();
 * $user_id = 56;
 * $code = $dn->encode($user_id);
 * $code = '61204922299213336';//测试的时候保存一个code，然后修改后16位的任意数字都会导致校验失败
 * e('EN'.$code);
 * e('DE'.$dn->decode($code));.
 */
class Dencrypt
{
    /**
     * 密码盘.
     *
     * @var array
     */
    private $salt = [
        '3742590186',
        '1579204368',
        '4012896753',
        '0819327645',
        '1348096527',
        '6824150973',
        '2906734851',
        '5943107862',
        '9516720384',
        '8756041923',
    ];

    private $fixed_code = '61';
    private $length = 10;

    public function setLength($length)
    {
        $this->length = $length;
    }

    /**
     * 对纯数字生成固定位数的密钥
     * 61 8 3609 9985947242 前两位固定，8校验码 3609是随机码 后面10位是用户ID，不足用0补充.
     *
     * @param $user_id
     *
     * @return string
     */
    public function encode($user_id)
    {
        $user_id = str_pad($user_id, $this->length, '0', STR_PAD_LEFT);

//        $salt_rand = mt_rand(0, 9);
        //由MOD10算法生成校验码
        $salt_rand = $this->generateNumber($user_id);
        $rand = substr(str_shuffle($this->salt[$salt_rand]), 0, 4);

        $str = $this->fixed_code.$salt_rand;

        $len = strlen($user_id);
        $rlen = strlen($rand);
        $slen = count($this->salt);

        //对用户ID加密
        for ($i = 0; $i < $len; ++$i) {
            $k1 = $i % $rlen;
            $k2 = $i % $slen;

            $rval1 = $rand[$k1];

            $user_id[$i] = ($user_id[$i] + $this->salt[$k2][$rval1]) % 10;
        }

        //对随机码加密
        //    e('RAND:' . $rand);
        $sr = $salt_rand;
        for ($j = 0; $j < $rlen; ++$j) {
            $rand[$j] = $sr = ($rand[$j] + $sr) % 10;
        }

        return $str.$rand.$user_id;
    }

    /**
     * 解码，如果返回0则表示校验不通过.
     *
     * @param $str
     *
     * @return int
     */
    public function decode($str)
    {
        $user_id = substr($str, -$this->length);
        $salt_rand = (int) substr($str, 2, 1);
        $rand = substr($str, 3, 4);

        $len = strlen($user_id);
        $rlen = strlen($rand);
        $slen = count($this->salt);

        //对随机码解密
        $sr = $salt_rand;
        for ($j = 0; $j < $rlen; ++$j) {
            if ($rand[$j] >= $sr) {
                $rand[$j] = $rand[$j] - $sr;
            } else {
                $rand[$j] = 10 - abs($rand[$j] - $sr);
            }

            $sr = ($rand[$j] + $sr) % 10;
        }

        //对用户ID加密
        for ($i = 0; $i < $len; ++$i) {
            $k1 = $i % $rlen;
            $k2 = $i % $slen;

            $rval1 = $rand[$k1];

            if ($user_id[$i] >= $this->salt[$k2][$rval1]) {
                $user_id[$i] = $user_id[$i] - $this->salt[$k2][$rval1];
            } else {
                $user_id[$i] = 10 - abs($user_id[$i] - $this->salt[$k2][$rval1]);
            }
        }

        if ($this->validateNumber($user_id.$salt_rand)) {
            return ltrim($user_id, '0');
        } else {
            return 0;
        }
    }

    /**
     * Luhn check.
     *
     * @author WN
     *
     * @param int $number
     *
     * @return bool
     */
    public function validateNumber($number)
    {
        return (bool) !$this->checksum($number, true);
    }

    /**
     * @author WN
     *
     * @param int $number
     *
     * @return int
     */
    public function generateNumber($number)
    {
        return $this->checksum($number);
    }

    /**
     * @author WN
     *
     * @param int  $number
     * @param bool $check  Set to true if you are calculating checksum for validation
     *
     * @return int
     */
    private function checksum($number, $check = false)
    {
        $data = str_split(strrev($number));
        $sum = 0;
        foreach ($data as $k => $v) {
            $tmp = $v + $v * (int) (($k % 2) xor !$check);
            if ($tmp > 9) {
                $tmp -= 9;
            }
            $sum += $tmp;
        }
        $sum %= 10;

        return (int) $sum == 0 ? 0 : 10 - $sum;
    }
}
