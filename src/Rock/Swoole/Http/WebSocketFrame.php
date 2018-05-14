<?php
/**
 * Created by PhpStorm.
 * User: E
 * Date: 2018/5/14
 * Time: 17:12
 */
namespace Rock\Swoole\Http;

class WebSocketFrame
{
    public $finish = false;
    public $opcode;
    public $data;

    public $length;
    public $rsv1;
    public $rsv2;
    public $rsv3;
    public $mask;
}