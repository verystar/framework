<?php

namespace Very\Tests\Config;

use PHPUnit\Framework\TestCase;
use Very\Config;

class RepositoryTest extends TestCase
{
    /**
     * @var \Very\Config
     */
    protected $repository;

    public function setUp()
    {
        $this->repository = new Config(__DIR__.'/config');
        parent::setUp();
    }

    public function testConstruct()
    {
        $this->assertInstanceOf(Config::class, $this->repository);
    }

    public function testHasIsTrue()
    {
        $this->assertTrue($this->repository->has('test.foo'));
    }

    public function testHasIsFalse()
    {
        $this->assertFalse($this->repository->has('test.not-exist'));
    }

    public function testGet()
    {
        $this->assertSame('bar', $this->repository->get('test.foo'));
    }

    public function testGetWithDefault()
    {
        $this->assertSame('default', $this->repository->get('test.not-exist', 'default'));
    }

    public function testSet()
    {
        $this->repository->set('test.key', 'value');
        $this->assertSame('value', $this->repository->get('test.key'));
    }

    public function testSetArray()
    {
        $this->repository->set([
            'test.key1' => 'value1',
            'test.key2' => 'value2',
        ]);
        $this->assertSame('value1', $this->repository->get('test.key1'));
        $this->assertSame('value2', $this->repository->get('test.key2'));
    }

    public function testPrepend()
    {
        $this->repository->prepend('test.array', 'xxx');
        $this->assertSame('xxx', $this->repository->get('test.array.0'));
    }

    public function testPush()
    {
        $this->repository->push('test.array', 'xxx');
        $this->assertSame('xxx', $this->repository->get('test.array.3'));
    }
}
