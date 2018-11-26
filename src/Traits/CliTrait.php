<?php
/**
 * Created by PhpStorm.
 * User: 姚志博
 * Date: 2018/11/19
 * Time: 17:36
 */
namespace YSwoole\Traits;

trait CliTrait
{
    public function info($msg, $verbosity = null)
    {
        echo  $msg . "\n";
    }
}