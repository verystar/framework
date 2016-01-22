<?php
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 1/22/16 5:51 PM
 */

use Very\Http\Request;

class RequestTest extends PHPUnit_Framework_TestCase {

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