<?php

namespace Very\Encryption;

/**
 * PHP Google two-factor authentication module.
 *
 * See http://www.idontplaydarts.com/2011/07/google-totp-two-factor-authentication-for-php/
 * for more details
 *
 * @author     Phil (Orginal author of this class)
 * @author     change for fifsky
 **/
class OTP
{
    /**
     * Interval between key regeneration.
     */
    private $key_regen = 30;

    /**
     * Length of the Token generated.
     */
    private $length = 6;

    public function setLength($length)
    {
        $this->length = $length;
    }

    /**
     * Generate a digit secret key in base32 format.
     *
     * @param string $prefix
     *
     * @return string
     */
    public function generateSecretKey($prefix = '')
    {
        return base32_encode(sha1(uniqid($prefix, true), true));
    }

    /**
     * Returns the current Unix Timestamp devided by the KEY_REGENERATION
     * period.
     *
     * @return int
     **/
    public function getTimestamp()
    {
        return floor(microtime(true) / $this->key_regen);
    }

    /**
     * Takes the secret key and the timestamp and returns the one time
     * password.
     *
     * @param string $key     - Secret key in binary form.
     * @param int    $counter - Timestamp as returned by getTimestamp.
     *
     * @return string
     */
    public function generateCode($key, $counter = null)
    {
        if ($counter === null) {
            $counter = $this->getTimestamp();
        }

        $key = base32_decode($key);

        // Counter must be 64-bit int
        $bin_counter = pack('N*', 0).pack('N*', $counter);
        // 获取HASH值
        $hash = hash_hmac('sha1', $bin_counter, $key, true);
        // 进行混码计算
        $offset = ord($hash[19]) & 0xf;
        $secret_code = (
                ((ord($hash[$offset + 0]) & 0x7f) << 24) |
                ((ord($hash[$offset + 1]) & 0xff) << 16) |
                ((ord($hash[$offset + 2]) & 0xff) << 8) |
                (ord($hash[$offset + 3]) & 0xff)
            ) % pow(10, $this->length);
        // 密码不足$length位补齐
        return str_pad($secret_code, $this->length, '0', STR_PAD_LEFT);
    }

    /**
     * Verifies a user inputted key against the current timestamp. Checks $window
     * keys either side of the timestamp.
     *
     * @param string $key
     * @param string $code          - User specified key
     * @param int    $window        容错
     * @param bool   $use_timestamp 自定义的时间戳
     *
     * @return bool
     **/
    public function verify($key, $code, $window = 4, $use_timestamp = null)
    {
        if ($use_timestamp === null) {
            $timeStamp = $this->getTimestamp();
        } else {
            $timeStamp = (int) $use_timestamp;
        }

        for ($ts = $timeStamp - $window; $ts <= $timeStamp + $window; ++$ts) {
            if ($this->generateCode($key, $ts) == $code) {
                return true;
            }
        }

        return false;
    }

    /**
     * Creates a QR code url.
     *
     * @param $company
     * @param $holder
     * @param $secret
     *
     * @return string
     */
    public function getQRCodeUrl($company, $holder, $secret)
    {
        return 'otpauth://totp/'.urlencode($company.':'.$holder).'?secret='.$secret.'&issuer='.urlencode($company).'';
    }
}
