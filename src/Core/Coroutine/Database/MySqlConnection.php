<?php
/**
 * Created by PhpStorm.
 * User: 姚志博
 * Date: 2018/11/22
 * Time: 16:36
 */

namespace YSwoole\Core\Coroutine\Database;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Illuminate\Database\MySqlConnection as BaseConnection;

class MySqlConnection extends BaseConnection
{
    public function getPdo()
    {
        if ($this->pdo instanceof Closure) {
            return $this->pdo = \Swoole\Coroutine::call_user_func($this->pdo);
        }

        return $this->pdo;
    }
    /**
     * Handle a query exception that occurred during query execution.
     *
     * @param  \Illuminate\Database\QueryException  $e
     * @param  string    $query
     * @param  array     $bindings
     * @param  \Closure  $callback
     * @return mixed
     *
     * @throws \Illuminate\Database\QueryException
     */
    protected function tryAgainIfCausedByLostConnection(QueryException $e, $query, $bindings, Closure $callback)
    {
        // https://github.com/swoole/swoole-src/blob/a414e5e8fec580abb3dbd772d483e12976da708f/swoole_mysql_coro.c#L1140
        if ($this->causedByLostConnection($e->getPrevious()) || Str::contains($e->getMessage(), ['is closed', 'is not established'])) {
            $this->reconnect();

            return $this->runQueryCallback($query, $bindings, $callback);
        }

        throw $e;
    }
}
