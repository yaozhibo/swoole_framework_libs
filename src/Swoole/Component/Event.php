<?php
namespace Swoole\Component;

use Swoole\Exception;
use Swoole\IFace;

class Event
{
    /**
     * @var IFace\Queue
     */
    protected $_queue;
    protected $_handles = array();

    /**
     * @var \swoole_atomic
     */
    protected $_atomic;
    protected $_workers = array();

    protected $config;
    protected $async = false;

    function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * 处理器
     * @param $type
     * @param $data
     * @return mixed
     */
    protected function _execute($type, $data)
    {
        if (!isset($this->_handles[$type]))
        {
            $handlers = array();
            $path = \Swoole::$app_path.'/events/'.$type.'.php';
            if (is_file($path))
            {
                $_conf = include $path;
                if ($_conf and isset($_conf['handlers']) and is_array($_conf['handlers']))
                {
                    foreach ($_conf['handlers'] as $h)
                    {
                        if (class_exists($h))
                        {
                            $object = new $h;
                            if ($object instanceof IFace\EventHandler)
                            {
                                $handlers[] = $object;
                                continue;
                            }
                        }
                        trigger_error("invalid event handler[$type|$h]", E_USER_WARNING);
                    }
                }
            }
            $this->_handles[$type] = $handlers;
        }

        foreach($this->_handles[$type] as $handler)
        {
            /**
             * @var  $handler  IFace\EventHandler
             */
            $handler->trigger($type, $data);
        }
        return true;
    }

    /**
     * 触发事件
     * @param $type
     * @param $data
     * @return mixed
     * @throws Exception\NotFound
     */
    function trigger($type, $data)
    {
        /**
         * 异步，将事件压入队列
         */
        if (isset($this->config['async']) && $this->config['async'])
        {
            return $this->getQueueInstance()->push(array('type' => $type, 'data' => $data));
        }
        /**
         * 同步，直接在引发事件时处理
         */
        else
        {
            return $this->_execute($type, $data);
        }
    }

    function _worker()
    {
        $queue = $this->getQueueInstance();

        while ($this->_atomic->get() == 1)
        {
            $event = $queue->pop();
            if ($event)
            {
                $this->_execute($event['type'], $event['data']);
            }
            else
            {
                usleep(100000);
            }
        }
    }

    /**
     * @param int $worker_num
     * @param bool $daemon
     * @throws Exception\NotFound
     */
	function runWorker($worker_num = 1, $daemon = false)
    {
        if ($worker_num > 1 or $daemon)
        {
            if (!class_exists('\swoole\process'))
            {
                throw new Exception\NotFound("require swoole extension");
            }
            if ($worker_num < 0 or $worker_num > 1000)
            {
                $worker_num = 200;
            }
        }
        else
        {
            $this->_atomic = new \swoole_atomic(1);
            $this->_worker();
            return;
        }

        if ($daemon)
        {
            \swoole_process::daemon();
        }

        $this->_atomic = new \swoole_atomic(1);
        for ($i = 0; $i < $worker_num; $i++)
        {
            $process = new \swoole\process(array($this, '_worker'), false, false);
            $process->start();
            $this->_workers[] = $process;
        }

        \swoole_process::signal(SIGCHLD, function() {
            while(true) {
                $exitProcess = \swoole_process::wait(false);
                if ($exitProcess)
                {
                    foreach ($this->_workers as $k => $p)
                    {
                        if ($p->pid == $exitProcess['pid'])
                        {
                            if ($this->_atomic->get() == 1)
                            {
                                $p->start();
                            }
                            else
                            {
                                unset($this->_workers[$k]);
                                if (count($this->_workers) == 0)
                                {
                                    swoole_event_exit();
                                }
                            }
                        }
                    }
                }
                else
                {
                    break;
                }
            }
        });

        \swoole_process::signal(SIGTERM, function() {
            //停止运行
            $this->_atomic->set(0);
        });
    }

    protected function getQueueInstance()
    {
        $class = $this->config['type'];
        if (!class_exists($class))
        {
            throw new Exception\NotFound("class $class not found.");
        }

        if (is_null($this->_queue))
        {
            $this->_queue = new $class($this->config);
        }

        return $this->_queue;
    }
}
