<?php
/**
 * Created by PhpStorm.
 * User: 姚志博
 * Date: 2018/11/24
 * Time: 10:13
 */
return [
    'swoole_mysql_coroutine' => [
        'driver'      => 'mysql-coroutine',
        'host'        => env('DB_HOST', '127.0.0.1'),
        'port'        => env('DB_PORT', '3306'),
        'database'    => env('DB_DATABASE', 'forge'),
        'username'    => env('DB_USERNAME', 'forge'),
        'password'    => env('DB_PASSWORD', ''),
        'unix_socket' => env('DB_SOCKET', ''),
        'charset'     => 'utf8mb4',
        'collation'   => 'utf8mb4_unicode_ci',
        'prefix'      => '',
        'strict'      => false,
        'engine'      => null,
    ]

];