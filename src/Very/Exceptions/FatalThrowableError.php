<?php
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 24/02/2017 2:01 PM
 */

namespace Very\Exceptions;

/**
 * Fatal Throwable Error.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class FatalThrowableError extends FatalErrorException
{
    public function __construct(\Throwable $e)
    {
        if ($e instanceof \ParseError) {
            $message = 'Parse error: '.$e->getMessage();
            $severity = E_PARSE;
        } elseif ($e instanceof \TypeError) {
            $message = 'Type error: '.$e->getMessage();
            $severity = E_RECOVERABLE_ERROR;
        } else {
            $message = $e->getMessage();
            $severity = E_ERROR;
        }

        parent::__construct($message,$e->getCode(),$severity,$e->getFile(),$e->getLine());
        $this->setTrace($e->getTrace());
    }
}