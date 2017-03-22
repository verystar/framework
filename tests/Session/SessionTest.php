<?php

namespace Very\Tests\Config;

use PHPUnit\Framework\TestCase;
use Very\Session\SessionManager;

class SessionTest extends TestCase
{
    /**
     * @var \Very\Session\SessionManager
     */
    protected $session;

    public function setUp()
    {
        $this->session = new SessionManager();

        $this->session->put([
            'foo'    => 'bar',
            'bagged' => ['name' => 'fifsky']
        ]);

        parent::setUp();
    }

    public function testConstruct()
    {
        $this->assertInstanceOf(SessionManager::class, $this->session);
    }

    public function testSession()
    {
        $this->assertSame('bar', $this->session->get('foo'));
        $this->assertEquals('baz', $this->session->get('bar', 'baz'));
        $this->assertTrue($this->session->has('foo'));
        $this->assertFalse($this->session->has('bar'));
        $this->assertTrue($this->session->isStarted());
        $this->session->put('baz', 'boom');
        $this->assertTrue($this->session->has('baz'));
    }
}
