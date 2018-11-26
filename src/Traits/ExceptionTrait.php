<?php
/**
 * Created by PhpStorm.
 * User: 姚志博
 * Date: 2018/11/19
 * Time: 17:22
 */

namespace YSwoole\Traits;

use YSwoole\Exceptions\PublicException;

trait ExceptionTrait
{
    /**
     * @param $msg
     * @throws PublicException
     */
    public function http_error_30000($msg)
    {
        throw new PublicException($msg, 30000);
    }
}