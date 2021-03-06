# swoole_framework_libs
This is a vendor base on swoole, to speed up laravel/lumen.

# install
<pre>composer require yaozhibo/swoole_framework_libs</pre>

# register
- modify config/app.php
<pre>
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
</pre>
# publish
<pre>
php artisan vendor:publish --provider="YSwoole\YSwooleHttpProvider"
</pre>

# config
- modify http server ip and port in .env
<pre>
SWOOLE_HTTP_HOST=ip       #default:0.0.0.0
SWOOLE_HTTP_PORT=port     #default:8333
SWOOLE_HTTP_DAEMONZE=true #default:false
</pre>

# db.connection
- modify db connection in .env to enable mysql coroutine
<pre>
DB_CONNECTION=swoole_mysql_coroutine
</pre>

# controller
if your php version is lower than 7.1, you need to use 
<pre>php artisan make:yscontroller</pre>
to generate controller, or you could not use controller to finish mysql operation.
