<?php

namespace Very\Tests\Config;

use PHPUnit\Framework\TestCase;
use Very\Cookie\CookieJar;

class CookieTest extends TestCase
{
    /**
     * @var \Very\Cookie\CookieJar
     */
    protected $cookie;

    public function setUp()
    {
        $this->cookie = new CookieJar();
        $this->cookie->set('color', 'blue', '/path', '/domain', true, false);

        parent::setUp();
    }

    public function testConstruct()
    {
        $this->assertInstanceOf(CookieJar::class, $this->cookie);
    }

    public function testSession()
    {
        $this->assertEquals('blue', $this->cookie->get('color'));
        $this->assertEquals('red', $this->cookie->get('color2','red'));
        $this->cookie->delete('color');
        $this->assertNull($this->cookie->get('color'));
    }
}
