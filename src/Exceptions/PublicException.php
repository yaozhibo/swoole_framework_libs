<?php

namespace YSwoole\Exceptions;

use YSwoole\Utils\ResultHelper;
use Exception;

class PublicException extends Exception
{
    protected $data;

    public function __construct($message = "", $code = 0, $data = false, Exception $previous = NULL)
    {
        $this->data = $data;
        parent::__construct($message, $code, $previous);
    }

    public function getData()
    {
        return $this->data;
    }

    public function render($request)
    {
        if ($request->expectsJson())
            return ResultHelper::json($this->getData(), $this->getMessage(), $this->getCode());
        else
            return response()->view('errors.error', ['status' => $this->getCode(), 'info' => $this->getMessage(), 'data' => $this->getData()]);
    }
}
