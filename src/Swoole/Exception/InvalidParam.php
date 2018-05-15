<?php
namespace Swoole\Exception;

/**
 * 模块不存在
 * Class NotFound
 * @package Swoole
 */
class InvalidParam extends \Exception
{
    const ERROR_REQUIRED = 1000;
    const ERROR_TYPE_INCORRECTLY = 1001;
    const ERROR_USER_DEFINED = 1002;

    public $key;
}
