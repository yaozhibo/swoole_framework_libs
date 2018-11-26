# swoole_framework_libs

install
composer require yaozhibo/swoole_framework_libs:1.0.2

register
- modify config/app.php
'providers' =>
[
.
.
.
\YSwoole\YSwooleHttpProvider::class,
\YSwoole\Providers\LaraServiceProvider::class
//if your app was biult in Lumen, \YSwoole\Providers\LumenServiceProvider::class instead.
.
.
.
]

publish
php artisan vendor:publish --provider="YSwoole\YSwooleHttpProvider"

config
- modify http server ip and port in config/yswoole_http.php
return [
    'server' => [
      'host' => env('SWOOLE_HTTP_HOST', ip),
      'port' => env('SWOOLE_HTTP_PORT', port),
      .
      .
      .
    ]
]      

db.connection
- modify db connection in .env to enable mysql coroutine
DB_CONNECTION=swoole_mysql_coroutine

controller
if your php version is lower than 7.1, you need to use 
php artisan make:yscontroller
to generate controller, or you could not use controller to finish mysql operation.
