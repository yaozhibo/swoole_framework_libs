<?php
namespace Swoole\Queue;

/**
 * Redis内存队列
 */
class Redis implements \Swoole\IFace\Queue
{
    protected $redis_factory_key;
    protected $key = 'swoole:queue';

    function __construct($config)
    {
        if (empty($config['id']))
        {
            $config['id'] = 'master';
        }
        $this->redis_factory_key = $config['id'];
        if (!empty($config['key']))
        {
            $this->key = $config['key'];
        }
    }

    /**
     * 出队
     * @return bool|mixed
     */
    function pop()
    {
        $ret = \Swoole::$php->redis($this->redis_factory_key)->lPop($this->key);
        if ($ret)
        {
            return unserialize($ret);
        }
        else
        {
            return false;
        }
    }

    /**
     * 入队
     * @param $data
     * @return int
     */
    function push($data)
    {
        return \Swoole::$php->redis($this->redis_factory_key)->lPush($this->key, serialize($data));
    }
}
