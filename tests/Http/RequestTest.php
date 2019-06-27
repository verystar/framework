<?php
/**
 * Created by PhpStorm.
 * User: fifsky
 * Date: 1/22/16 5:51 PM
 */

use Very\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase {

    public function testMethod() {
        $data = ['a' => 1, 'b' => 2];
        $_GET = $data;

        $e = new Request();
        $this->assertEquals(1, $e->get('a'));
        $this->assertEquals(['a' => 1, 'b' => 2], $e->get());
        $this->assertEquals(3, $e->get('c', 3));
        $this->assertNull($e->get('d'));
    }
}