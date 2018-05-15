<?php
namespace Swoole;

class Config extends \ArrayObject
{
    protected $config;
    protected $config_path = array();
    public $dir_num = 0;
    static $debug = false;
    static $active = false;

    function setPath($dir)
    {
        $_dir = realpath($dir);
        if ($_dir === false)
        {
            error:
            if (self::$debug)
            {
                trigger_error("config dir[$dir] not exists.", E_USER_WARNING);
            }
            return false;
        }
        $dir = $_dir;
        if (!is_dir($dir))
        {
            goto error;
        }
        if (in_array($dir, $this->config_path))
        {
            if (self::$debug)
            {
                trigger_error("config path[$dir] is already added.", E_USER_WARNING);
            }
            return false;
        }
        $this->config_path[] = $dir;
        self::$active = true;
        return true;
    }

    function offsetGet($index)
    {
        if (!isset($this->config[$index]))
        {
            $this->load($index);
        }
        return isset($this->config[$index]) ? $this->config[$index] : false;
    }

	function load($index)
	{
        foreach ($this->config_path as $path)
        {
            $filename = $path . '/' . $index . '.php';
            if (is_file($filename))
            {
                $retData = include $filename;
                if (empty($retData) and self::$debug)
                {
                    trigger_error(__CLASS__ . ": $filename no return data");
                }
                else
                {
                    $this->config[$index] = $retData;
                }
            }
            elseif (self::$debug)
            {
                trigger_error(__CLASS__ . ": $filename not exists");
            }
        }
	}

    function offsetSet($index, $newval)
    {
        $this->config[$index] = $newval;
    }

    function offsetUnset($index)
    {
        unset($this->config[$index]);
    }

    function offsetExists($index)
    {
        if (!isset($this->config[$index]))
        {
            $this->load($index);
        }
        return isset($this->config[$index]);
    }
}
