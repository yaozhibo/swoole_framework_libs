<?php
/**
 * Created by PhpStorm.
 * User: 姚志博
 * Date: 2018/11/20
 * Time: 9:56
 */

namespace YSwoole;

use Illuminate\Container\Container;
use YSwoole\Commands\YSControllerMakeCommand;
use Illuminate\Routing\ControllerDispatcher;
use PHPUnit\Framework\Constraint\IsJson;
use YSwoole\Core\Coroutine\Database\ConnectionFactory;
use YSwoole\Core\Coroutine\Database\Connectors\MySqlConnector;
use Illuminate\Support\ServiceProvider;
use Swoole\Http\Server as HttpServer;
use YSwoole\Commands\HttpServerCommand;
use YSwoole\Core\Coroutine\Database\MySqlConnection;
use YSwoole\Core\Coroutine\Routing\Controller;
use YSwoole\Core\Manager;
use YSwoole\Core\Facades\Server;
use YSwoole\Task\Connectors\SwooleTaskConnector;
use DB;

class YSwooleHttpProvider extends ServiceProvider
{
    public $defer = false;

    protected $isWebsocket = false;

    protected static $server;

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/configs/yswoole_http.php' => base_path('config/yswoole_http.php'),
        ]);
    }

    public function register()
    {
        $this->requireHelpers();

        $this->mergeConfigs();

        $this->registerCommands();

        $this->registerServer();

        $this->registerDatabaseDriver();

        $this->registerSwooleQueueDriver();

    }

    public function requireHelpers()
    {
        require_once __DIR__ . '/Core/helpers.php';
    }

    protected function mergeConfigs()
    {
        $this->mergeConfigFrom(__DIR__ . '/configs/yswoole_http.php', 'yswoole_http');
        $this->mergeConfigFrom(__DIR__ . '/configs/yswoole_coroutine_db.php', 'database.connections');
    }

    protected function registerCommands()
    {
        $this->commands([
            HttpServerCommand::class,
            YSControllerMakeCommand::class
        ]);
    }

    protected function createSwooleHttpServer()
    {
        $server = HttpServer::class;
        $host = $this->app['config']->get('yswoole_http.server.host');
        $port = $this->app['config']->get('yswoole_http.server.port');
        $socketType = $this->app['config']->get('swoole_http.server.socket_type', SWOOLE_SOCK_TCP);

        static::$server = new $server($host, $port, SWOOLE_PROCESS, $socketType);

    }

    protected function configureSwooleHttpServer()
    {
        $config = $this->app['config'];
        $options = config('yswoole_http.server.options');

        if ($config->get('queue.default') !== 'swoole') {
            unset($config['task_worker_num']);
        }

        static::$server->set($options);
    }

    protected function registerServer()
    {
        $this->app->singleton(Server::class, function () {
            if (is_null(static::$server)) {
                $this->createSwooleHttpServer();
                $this->configureSwooleHttpServer();
            }
            return static::$server;
        });
        $this->app->alias(Server::class, 'swoole.server');
    }

    protected function registerDatabaseDriver()
    {
        $this->app->resolving('db', function ($db) {
            $db->extend('mysql-coroutine', function ($config, $name) {
                $config['name'] = $name;
                $connection = function () use ($config) {
                    return (new MySqlConnector())->connect($config);
                };

                return new MySqlConnection(
                    $connection,
                    $config['database'],
                    $config['prefix'],
                    $config
                );
            });
        });

    }

    protected function registerSwooleQueueDriver()
    {
        $this->app->afterResolving('queue', function ($manager) {
            $manager->addConnector('swoole', function () {
                return new SwooleTaskConnector($this->app->make(Server::class));
            });
        });
    }
}