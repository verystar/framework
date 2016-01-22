<?php
use Very\Encryption\ThirdDES;
use Very\Encryption\AES;
use Very\Encryption\OTP;
use Very\Encryption\DEncrypt;

/**
 * 3DES加密
 */
class EncrypterTest extends PHPUnit_Framework_TestCase {

    public function testDES() {
        $e         = new ThirdDES(str_repeat('a', 16));
        $encrypted = $e->encode('foo');
        $this->assertNotEquals('foo', $encrypted);
        $this->assertEquals('foo', $e->decode($encrypted));
    }

    public function testAES() {
        $e         = new AES(str_repeat('a', 16));
        $encrypted = $e->encode('foo');
        $this->assertNotEquals('foo', $encrypted);
        $this->assertEquals('foo', $e->decode($encrypted));
    }

    public function testOTP() {
        $e = new OTP();

        $key = $e->generateSecretKey();
        $num = $e->generateCode($key);

        $this->assertNotEmpty($num);
        $this->assertTrue($e->verify($key, $num));
    }

    public function testDEncrypt() {
        $e         = new DEncrypt();
        $encrypted = $e->encode(123456);
        $this->assertNotEquals(123456, $encrypted);
        $this->assertEquals(123456, $e->decode($encrypted));
    }
}