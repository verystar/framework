<?php
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 24/02/2017 1:01 PM
 */

namespace Very\Contracts\Debug;

use Exception;

interface ExceptionHandler
{
    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Exception  $e
     */
    public function render(Exception $e);
}
