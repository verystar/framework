<?php

namespace Very\Tests\Hashing;

use PHPUnit\Framework\TestCase;
use Very\Hashing\Password;

class BcryptHasherTest extends TestCase
{
    public function testBasicHashing()
    {
        $value  = Password::make('password');
        $this->assertNotSame('password', $value);
        $this->assertTrue(Password::verify('password', $value));
        $this->assertFalse(Password::rehash($value));
        $this->assertTrue(Password::rehash($value, ['rounds' => 1]));
    }
}