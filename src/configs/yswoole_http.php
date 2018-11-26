<?php
/**
 * Created by PhpStorm.
 * User: 姚志博
 * Date: 2018/11/26
 * Time: 12:11
 */
use Swoole\Table;

return [
    'server' => [
        'host' => env('SWOOLE_HTTP_HOST', '0.0.0.0'),
        'port' => env('SWOOLE_HTTP_PORT', '8333'),
        'public_path' => base_path('public'),
        'handle_static_files' => env('SWOOLE_HANDLE_STATIC', true),
        'socket_type' => SWOOLE_SOCK_TCP,
        'options' => [
            'pid_file' => env('SWOOLE_HTTP_PID_FILE',base_path('storage/logs/yswoole_http.pid')),
            'log_file' => env('SWOOLE_HTTP_LOG_FILE', base_path('storage/logs/swoole_http.log')),
            'daemonize' => env('SWOOLE_HTTP_DAEMONZE', false),
            'reactor_num' => env('SWOOLE_HTTP_REACTOR_NUM', swoole_cpu_num()),
            'worker_num' => env('SWOOLE_HTTP_WORKER_NUM', swoole_cpu_num()),
            'task_worker_num' => env('SWOOLE_HTTP_TASK_WORKER_NUM', swoole_cpu_num()),
            'package_max_length' => 20 * 1024 * 1024,
            'buffer_output_size' => 10 * 1024 * 1024,
            'socket_buffer_size' => 128 * 1024 * 1024,
            'max_request' => 3000,
            'send_yield' => true,
            'ssl_cert_file' => null,
            'ssl_key_file' => null,
        ],
    ],
    
    'ob_output' => env('SWOOLE_OB_OUTPUT', true),

    'pre_resolved' => [
        'view', 'files', 'session', 'session.store', 'routes',
        'db', 'db.factory', 'cache', 'cache.store', 'config', 'cookie',
        'encrypter', 'hash', 'router', 'translator', 'url', 'log',
    ],

    'instances' => [
        //
    ],
    'resets' => [
        YSwoole\Core\Resets\BindRequest::class,
        YSwoole\Core\Resets\ClearInstances::class,
        YSwoole\Core\Resets\RebindKernelContainer::class,
        YSwoole\Core\Resets\RebindRouterContainer::class,
        YSwoole\Core\Resets\RebindViewContainer::class,
        YSwoole\Core\Resets\ResetConfig::class,
        YSwoole\Core\Resets\ResetCookie::class,
        YSwoole\Core\Resets\ResetProviders::class,
        YSwoole\Core\Resets\ResetSession::class

    ],
    'providers' => [
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,
    ],
    /*'tables' => [
         'table_name' => [
             'size' => 1024,
             'columns' => [
                 ['name' => 'column_name', 'type' => Table::TYPE_STRING, 'size' => 1024],
             ]
         ],
    ]*/
];