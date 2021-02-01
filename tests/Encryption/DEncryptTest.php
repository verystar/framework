<?php

namespace Encryption;

use Very\Encryption\DEncrypt;
use PHPUnit\Framework\TestCase;

class DEncryptTest extends TestCase
{

    public function testEncode()
    {
        $dencrypt = new DEncrypt();
        $dencrypt->setLength(8);
        $dencrypt->setRandomLen(3);
        $dencrypt->setFixedCode('');

        $user_id = "10010022";

        $init_code = $dencrypt->encode($user_id);
        $this->assertEquals(strlen($init_code),12);
    }

    public function testDecode()
    {
        $dencrypt = new DEncrypt();
        $dencrypt->setLength(8);
        $dencrypt->setRandomLen(3);
        $dencrypt->setFixedCode('');

        $init_code = "119046012848";

        $user_id = $dencrypt->decode($init_code);
        $this->assertEquals($user_id,10010022);
    }
}
