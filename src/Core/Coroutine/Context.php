<?php
/**
 * Created by PhpStorm.
 * User: 姚志博
 * Date: 2018/11/21
 * Time: 15:00
 */

namespace YSwoole\Core\Coroutine;

use Swoole\Coroutine;
use Illuminate\Contracts\Container\Container;

class Context
{
    protected static $apps = [];

    protected static $data = [];

    public static function getApp()
    {
        return static::$apps[static::getCoroutineId()] ?? null;
    }

    public static function setApp(Container $app)
    {
        static::$apps[static::getCoroutineId()] = $app;
    }

    public static  function getData(string $key)
    {
        return static::$data[static::getCoroutineId()][$key] ?? null;
    }

    public static function setData(string $key, $value)
    {
        static::$data[static::getCoroutineId()][$key] = $value;
    }

    public static function removeData(string $key)
    {
        unset(static::$data[static::getCoroutineId()][$key]);
    }

    public static function getDataKeys()
    {
        return array_keys(static::$data[static::getCoroutineId()] ?? []);
    }

    public static function clear()
    {
        unset(static::$apps[static::getCoroutineId()]);
        unset(static::$data[static::getCoroutineId()]);
    }

    public static function getCoroutineId()
    {
        return Coroutine::getuid();
    }
}