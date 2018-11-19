<?php
/**
 * Created by PhpStorm.
 * User: E
 * Date: 2018/11/19
 * Time: 17:16
 */
namespace YSwoole\Utils;

class Process
{
    public static function kill($server_pid, $sig)
    {
        return \Swoole::$php->os->kill($server_pid, $sig);
    }
}
