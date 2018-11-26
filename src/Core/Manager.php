<?php
/**
 * Created by PhpStorm.
 * User: 姚志博
 * Date: 2018/11/20
 * Time: 10:57
 */

namespace YSwoole\Core;

use Swoole\Http\Server;
use Illuminate\Contracts\Container\Container;
use YSwoole\Exceptions\PublicException;
use YSwoole\Traits\ExceptionTrait;
use YSwoole\Traits\InteractsWithSwooleTable;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Facade;
use YSwoole\Traits\WithApplication;
use YSwoole\Transformers\Request;
use YSwoole\Transformers\Response;

class Manager
{
    use ExceptionTrait,
        InteractsWithSwooleTable,
        WithApplication;

    protected $container;

    protected $framework;

    protected $basePath;

    protected $events = [
        'start', 'shutDown', 'workerStart', 'workerStop', 'packet',
        'bufferFull', 'bufferEmpty', 'task', 'finish', 'pipeMessage',
        'workerError', 'managerStart', 'managerStop', 'request'
    ];

    public function __construct(Container $container, $framework, $basePath = null)
    {
        $this->container = $container;

        // Setting php framework.
        $this->setFramework($framework);

        // Setting app base path.
        $this->setBasepath($basePath);

        $this->initialize();
    }

    public function run()
    {
        $this->container['swoole.server']->start();
    }

    public function stop()
    {
        $this->container['swoole.server']->shutdown();
    }

    protected function setFramework($framework)
    {
        $framework = strtolower($framework);

        if (! in_array($framework, ['laravel', 'lumen'])) {
            $this->http_error_30000('Invalid framework' . $framework);
        }

        $this->framework = $framework;
    }

    protected function setBasePath($basePath)
    {
        $this->basePath = base_path() ?? $basePath;
    }

    // Initializing.
    protected function initialize()
    {
        $this->createTables();
        $this->setSwooleServerListeners();
    }

    protected function setSwooleServerListeners()
    {
        foreach ($this->events as $event) {
            $listener = 'on' . ucfirst($event);

            if (method_exists($this, $listener)) {
                $this->container['swoole.server']->on($event, [$this, $listener]);
            } else {
                $this->container['swoole.server']->on($event, function () use ($event) {
                    $event = sprintf('swoole.%s', $event);

                    $this->container['events']->fire($event, func_get_args());
                });
            }
        }
    }

    public function onStart()
    {
        $this->setProcessName('master process');
        $this->createPidFile();
        $this->container['events']->fire('swoole.start', func_get_args());
    }

    public function onManagertart()
    {
        $this->setProcessName('manager process');
        $this->container['events']->fire('swoole.managerStart', func_get_args());
    }

    public function onWorkerStart($server)
    {
        $this->clearCache();
        $this->setProcessName('worker process');

        $this->container['events']->fire('swoole.workerStart', func_get_args());

        // don't init laravel app in task workers
        if ($server->taskworker) {
            return;
        }

        // clear events instance in case of repeated listeners in worker process
        Facade::clearResolvedInstance('events');

        // prepare laravel app
        $this->getApplication();

        // bind after setting app
        $this->bindToApp();
    }

    /**
     * "onRequest" listener.
     *
     * @param \Swoole\Http\Request $swooleRequest
     * @param \Swoole\Http\Response $swooleResponse
     */
    public function onRequest($swooleRequest, $swooleResponse)
    {
        $this->app['events']->fire('swoole.request');
        
        $handleStatic = $this->container['config']->get('yswoole_http.handle_static_files', true);
        $publicPath = $this->container['config']->get('yswoole_http.server.public_path', base_path('public'));


        try {
            // handle static file request first
            if ($handleStatic && Request::handleStatic($swooleRequest, $swooleResponse, $publicPath)) {
                return;
            }
            // transform swoole request to illuminate request
            $illuminateRequest = Request::make($swooleRequest)->toIlluminate();

            // set current request to sandbox
            $this->app['swoole.sandbox']->setRequest($illuminateRequest);
            // enable sandbox
            $this->app['swoole.sandbox']->enable();

            // handle request via laravel/lumen's dispatcher
            $illuminateResponse = $this->app['swoole.sandbox']->run($illuminateRequest);
            $response = Response::make($illuminateResponse, $swooleResponse);

            $response->send();

        } catch (Throwable $e) {
            try {
                $exceptionResponse = $this->app[ExceptionHandler::class]->render($illuminateRequest, $e);
                $response = Response::make($exceptionResponse, $swooleResponse);
                $response->send();
            } catch (Throwable $e) {
                $this->logServerError($e);
            }
        } finally {
            // disable and recycle sandbox resource
            $this->app['swoole.sandbox']->disable();
        }
    }

    /**
     * Reset on every request.
     */

    /**
     * Set onTask listener.
     */
    public function onTask($server, $taskId, $srcWorkerId, $data)
    {
        $this->container['events']->fire('swoole.task', func_get_args());

        try {
            // push websocket message
            if (is_array($data)) {

                // push async task to queue
            } elseif (is_string($data)) {
                $decoded = json_decode($data, true);

                if (JSON_ERROR_NONE === json_last_error() && isset($decoded['job'])) {
                    (new SwooleTaskJob($this->container, $server, $data, $taskId, $srcWorkerId))->fire();
                }
            }
        } catch (Throwable $e) {
            $this->logServerError($e);
        }
    }

    /**
     * Set onFinish listener.
     */
    public function onFinish($server, $taskId, $data)
    {
        // task worker callback
        return;
    }

    /**
     * Set onShutdown listener.
     */
    public function onShutdown()
    {
        $this->removePidFile();
    }

    /**
     * Set bindings to Laravel app.
     */
    protected function bindToApp()
    {
        $this->bindSandbox();
        $this->bindSwooleTable();

    }

    /**
     * Bind sandbox to Laravel app container.
     */
    protected function bindSandbox()
    {
        $this->app->singleton(Sandbox::class, function ($app) {
            return new Sandbox($app, $this->framework);
        });
        $this->app->alias(Sandbox::class, 'swoole.sandbox');
    }

    /**
     * Gets pid file path.
     *
     * @return string
     */
    protected function getPidFile()
    {
        return $this->container['config']->get('yswoole_http.server.options.pid_file');
    }

    /**
     * Create pid file.
     */
    protected function createPidFile()
    {
        $pidFile = $this->getPidFile();
        $pid = $this->container['swoole.server']->master_pid;

        file_put_contents($pidFile, $pid);
    }

    /**
     * Remove pid file.
     */
    protected function removePidFile()
    {
        $pidFile = $this->getPidFile();

        if (file_exists($pidFile)) {
            unlink($pidFile);
        }
    }

    /**
     * Clear APC or OPCache.
     */
    protected function clearCache()
    {
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    /**
     * Set process name.
     *
     * @codeCoverageIgnore
     * @param $process
     */
    protected function setProcessName($process)
    {
        // MacOS doesn't support modifying process name.
        if ($this->isMacOS() || $this->isInTesting()) {
            return;
        }
        $serverName = 'swoole_http_server';
        $appName = $this->container['config']->get('app.name', 'Laravel');

        $name = sprintf('%s: %s for %s', $serverName, $process, $appName);

        swoole_set_process_name($name);
    }

    /**
     * Indicates if the process is running in macOS.
     *
     * @return bool
     */
    protected function isMacOS()
    {
        return PHP_OS === 'Darwin';
    }

    /**
     * Indicates if it's in phpunit environment.
     *
     * @return bool
     */
    protected function isInTesting()
    {
        return defined('IN_PHPUNIT') && IN_PHPUNIT;
    }

    /**
     * Log server error.
     *
     * @param Throwable
     */
    public function logServerError(Throwable $e)
    {
        $this->container[ExceptionHandler::class]->report($e);
    }
}