<?php

namespace Very\Exceptions;

use Exception;
use Very\Http\Exception\HttpResponseException;
use Very\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;

class Handler implements ExceptionHandlerContract
{
    /**
     * Report or log an exception.
     *
     * @param  \Exception $e
     *
     * @return void
     *
     * @throws \Exception
     */
    public function report(Exception $e)
    {
        logger()->error('PHP Error', [
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'message' => $e->getMessage()
        ]);
    }

    /**
     * Render an exception into a response.
     *
     * @param  \Exception $e
     * @throws \Exception
     */
    public function render(Exception $e)
    {
        if ($e instanceof HttpResponseException) {
            /* error occurs */
            switch ($e->getCode()) {
                case HttpResponseException::ERR_NOTFOUND_CONTROLLER:
                case HttpResponseException::ERR_NOTFOUND_ACTION:
                case HttpResponseException::ERR_NOTFOUND_VIEW:
                    response()->setStatusCode(404);
                    echo $e->getMessage();
                    break;
                default :
                    echo 0, ':', $e->getMessage();
                    break;
            }
        } else {
            throw $e;
        }
    }

    public function shutdown()
    {

    }
}
