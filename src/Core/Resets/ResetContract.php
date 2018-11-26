<?php
/**
 * Created by PhpStorm.
 * User: 姚志博
 * Date: 2018/11/20
 * Time: 14:37
 */
namespace YSwoole\Core\Resets;

use YSwoole\Core\Sandbox;
use Illuminate\Contracts\Container\Container;

interface ResetContract
{
    public function handle(Container $app, Sandbox $sandbox);
}