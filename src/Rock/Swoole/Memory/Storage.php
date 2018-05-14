<?php
namespace Swoole\Memory;

use Swoole\Exception\Syscall;
use Swoole\Tool;

class Storage
{
    static $shmDir = '/dev/shm';
    static $separator = ':';

    protected $baseDir;
    protected $mode;

    function __construct($subdir = 'swoole', $mode = 0777)
    {
        $this->baseDir = self::$shmDir . '/' . $subdir;
        $this->mode = $mode;
        if (!is_dir($this->baseDir))
        {
            Syscall::mkdir($this->baseDir, $this->mode, true);
        }
    }

    protected function getFile($key, $createDir = false)
    {
        $file = $this->baseDir . '/' . str_replace(self::$separator, '/', trim($key, self::$separator));
        $dir = dirname($file);
        if ($createDir and !is_dir($dir))
        {
            Syscall::mkdir($dir, $this->mode, true);
        }
        return $file;
    }

    function get($key)
    {
        $file = $this->getFile($key);
        if (!is_file($file))
        {
            return false;
        }
        $res = Tool::readFile($file);
        if ($res)
        {
            return unserialize($res);
        }
        else
        {
            return false;
        }
    }

    function set($key, $value)
    {
        $file = $this->getFile($key, true);
        if (file_put_contents($file, serialize($value), LOCK_EX) === false)
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    function exists($key)
    {
        return is_file($this->getFile($key));
    }

    function scan($prefix)
    {
        $dir = $this->baseDir . '/' . str_replace(self::$separator, '/', trim($prefix, self::$separator));
        if (!is_dir($dir))
        {
            return false;
        }
        return Tool::scandir($dir);
    }

    function del($key)
    {
        $file = $this->getFile($key);
        return unlink($file);
    }
}