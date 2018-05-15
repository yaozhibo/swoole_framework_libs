<?php
namespace Swoole\Http;

use Swoole\IFace\Http;
use Swoole;

class LAMP implements Http
{
    function header($k,$v)
    {
        header($k.': '.$v);
    }

    function status($code)
    {
        header('HTTP/1.1 '. Swoole\Response::$HTTP_HEADERS[$code]);
    }

    function response($content)
    {
        exit($content);
    }

    function redirect($url, $mode = 302)
    {
        header("HTTP/1.1 " . Swoole\Response::$HTTP_HEADERS[$mode]);
        header("Location: " . $url);
    }

    function finish($content = null)
    {
        exit($content);
    }

    function setcookie($name, $value = null, $expire = null, $path = '/', $domain = null, $secure = null, $httponly = null)
    {
        setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    function getRequestBody()
    {
        return file_get_contents('php://input');
    }
}