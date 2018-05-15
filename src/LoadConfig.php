<?php
/**
 * Created by PhpStorm.
 * User: E
 * Date: 2018/5/15
 * Time: 9:25
 */

namespace Src;

class LoadConfig
{
    public function loadConfig()
    {
        require __DIR__."lib_config.php";
    }
}