<?php
/**
 * Created by PhpStorm.
 * User: 姚志博
 * Date: 2018/11/19
 * Time: 15:46
 */
namespace YSwoole\Commands;

use Throwable;
use Illuminate\Console\Command;
use YSwoole\Exceptions\PublicException;
use YSwoole\Traits\CliTrait;
use YSwoole\Utils\Process;
use YSwoole\Traits\ExceptionTrait;

class HttpServerCommand extends Command
{
    use ExceptionTrait,
        CliTrait;
    
    protected $signature = 'yswoole: http {action: start|stop|restart|reload|infos}';

    protected $description = 'Swoole Http Server command.';

    protected $action;

    protected $pid;

    protected $configs;

    /**
     * @throws PublicException
     */
    public function handle()
    {
        $this->sysEnvFilter();

        $this->phpVersionFilter();

    }

    protected function injectConfigs()
    {
        $this->configs = $this->laravel['config']->get('yswoole_http');
    }

    protected function runAction()
    {
        $this->{$this->action}();
    }

    /**
     * Run swoole_http_server.
     */
    protected function start()
    {
        if ($this->isRunning($this->getPid())) {
            $this->http_error_30000('Failed! swoole_http_server process is already running.');
            exit(1);
        }

        $host = $this->configs['server']['host'];
        $port = $this->configs['server']['port'];

        $this->info('Starting swoole http server...');
        $this->info("Swoole http server started: <http://{$host}:{$port}>");
        if ($this->isDaemon()) {
            $this->info('> (You can run this command to ensure the ' .
                'swoole_http_server process is running: ps aux|grep "swoole")');
        }

        $this->laravel->make('swoole.manager')->run();
    }

    /**
     * Stop swoole_http_server.
     */
    protected function stop()
    {
        $pid = $this->getPid();

        if (! $this->isRunning($pid)) {
            $this->http_error_30000("Failed! There is no swoole_http_server process running.");
            exit(1);
        }

        $this->info('Stopping swoole http server...');

        $isRunning = $this->killProcess($pid, SIGTERM, 15);

        if ($isRunning) {
            $this->http_error_30000('Unable to stop the swoole_http_server process.');
            exit(1);
        }

        // I don't known why Swoole didn't trigger "onShutdown" after sending SIGTERM.
        // So we should manually remove the pid file.
        $this->removePidFile();

        $this->info('> success');
    }

    /**
     * Restart swoole http server.
     */
    protected function restart()
    {
        $pid = $this->getPid();

        if ($this->isRunning($pid)) {
            $this->stop();
        }

        $this->start();
    }

    /**
     * Reload.
     */
    protected function reload()
    {
        $pid = $this->getPid();

        if (! $this->isRunning($pid)) {
            $this->http_error_30000("Failed! There is no swoole_http_server process running.");
            exit(1);
        }

        $this->info('Reloading swoole_http_server...');

        $isRunning = $this->killProcess($pid, SIGUSR1);

        if (! $isRunning) {
            $this->http_error_30000('> failure');
            exit(1);
        }

        $this->info('> success');
    }

    /**
     * Display PHP and Swoole misc info.
     */
    protected function infos()
    {
        $this->showInfos();
    }

    /**
     * Display PHP and Swoole miscs infos.
     *
     * @param bool $more
     */
    protected function showInfos()
    {
        $pid = $this->getPid();
        $isRunning = $this->isRunning($pid);
        $host = $this->configs['server']['host'];
        $port = $this->configs['server']['port'];
        $reactorNum = $this->configs['server']['options']['reactor_num'];
        $workerNum = $this->configs['server']['options']['worker_num'];
        $taskWorkerNum = $this->configs['server']['options']['task_worker_num'];
        $isWebsocket = $this->configs['websocket']['enabled'];
        $logFile = $this->configs['server']['options']['log_file'];

        $table = [
            ['PHP Version', 'Version' => phpversion()],
            ['Swoole Version', 'Version' => swoole_version()],
            ['Laravel Version', $this->getApplication()->getVersion()],
            ['Listen IP', $host],
            ['Listen Port', $port],
            ['Server Status', $isRunning ? 'Online' : 'Offline'],
            ['Reactor Num', $reactorNum],
            ['Worker Num', $workerNum],
            ['Task Worker Num', $isWebsocket ? $taskWorkerNum : 0],
            ['Websocket Mode', $isWebsocket ? 'On' : 'Off'],
            ['PID', $isRunning ? $pid : 'None'],
            ['Log Path', $logFile],
        ];

        $this->table(['Name', 'Value'], $table);
    }

    /**
     * Initialize command action.
     */
    protected function initAction()
    {
        $this->action = $this->argument('action');

        if (! in_array($this->action, ['start', 'stop', 'restart', 'reload', 'infos'])) {
            $this->http_error_30000("Invalid argument '{$this->action}'. Expected 'start', 'stop', 'restart', 'reload' or 'infos'.");
            exit(1);
        }
    }

    /**
     * If Swoole process is running.
     *
     * @param int $pid
     * @return bool
     */
    protected function isRunning($pid)
    {
        if (! $pid) {
            return false;
        }

        try {
            return Process::kill($pid, 0);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Kill process.
     *
     * @param int $pid
     * @param int $sig
     * @param int $wait
     * @return bool
     */
    protected function killProcess($pid, $sig, $wait = 0)
    {
        Process::kill($pid, $sig);

        if ($wait) {
            $start = time();

            do {
                if (! $this->isRunning($pid)) {
                    break;
                }

                usleep(100000);
            } while (time() < $start + $wait);
        }

        return $this->isRunning($pid);
    }

    /**
     * Get pid.
     *
     * @return int|null
     */
    protected function getPid()
    {
        if ($this->pid) {
            return $this->pid;
        }

        $pid = null;
        $path = $this->getPidPath();

        if (file_exists($path)) {
            $pid = (int) file_get_contents($path);

            if (! $pid) {
                $this->removePidFile();
            } else {
                $this->pid = $pid;
            }
        }

        return $this->pid;
    }

    /**
     * Get Pid file path.
     *
     * @return string
     */
    protected function getPidPath()
    {
        return $this->configs['server']['options']['pid_file'];
    }

    /**
     * Remove Pid file.
     */
    protected function removePidFile()
    {
        if (file_exists($this->getPidPath())) {
            unlink($this->getPidPath());
        }
    }

    /**
     * Return daemonize config.
     */
    protected function isDaemon()
    {
        return $this->configs['server']['options']['daemonize'];
    }

    /**
     * @throws PublicException
     */
    protected function sysEnvFilter()
    {
        switch (PHP_OS) {
            case 'WINNT':
            case 'WINDOWS':
                throw new PublicException('Invalid operated system.');
            default:
                return ;
        }
    }

    /**
     * @throws PublicException
     */
    protected function phpVersionFilter()
    {
        if (substr(PHP_VERSION, 0, 1) < 7) {
            throw new PublicException('Php version required above 7');
        }
    }
}