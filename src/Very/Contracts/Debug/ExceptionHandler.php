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
     * Report or log an exception.
     *
     * @param  \Exception  $e
     */
    public function report(Exception $e);

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Exception  $e
     */
    public function render(Exception $e);

    /**
     * PHP shutdown call
     * @return mixed
     */
    public function shutdown();
}
