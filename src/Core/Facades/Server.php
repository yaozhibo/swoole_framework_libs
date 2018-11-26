<?php
/**
 * Created by PhpStorm.
 * User: 姚志博
 * Date: 2018/11/20
 * Time: 16:25
 */

namespace YSwoole\Core\Facades;

use Illuminate\Support\Facades\Facade;

class Server extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'swoole.server';
    }
}