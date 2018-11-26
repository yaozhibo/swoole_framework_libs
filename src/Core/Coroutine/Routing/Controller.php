<?php
/**
 * Created by PhpStorm.
 * User: 姚志博
 * Date: 2018/11/25
 * Time: 11:05
 */
namespace YSwoole\Core\Coroutine\Routing;

use Illuminate\Routing\Controller as LaraController;
use Swoole\Coroutine;

class Controller extends LaraController
{
    public function callAction($method, $parameters)
    {
        $res = \Swoole\Coroutine::call_user_func_array([$this, $method], $parameters);
        return $res;
    }

}