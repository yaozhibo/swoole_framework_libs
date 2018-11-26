<?php
/**
 * Created by PhpStorm.
 * User: 姚志博
 * Date: 2018/11/22
 * Time: 15:29
 */

namespace YSwoole\Core\Coroutine\Database\Connectors;

use Exception;
use YSwoole\Exceptions\PublicException;
use Illuminate\Database\Connectors\MySqlConnector as LaraMySqlConnector;
use YSwoole\Core\Coroutine\Database\PDO;
use Illuminate\Support\Str;

class MySqlConnector extends LaraMySqlConnector
{
    public function connect(array $config)
    {
        $dsn = $this->getDsn($config);

        $options = $this->getOptions($config);

        // We need to grab the PDO options that should be used while making the brand
        // new connection instance. The PDO options control various aspects of the
        // connection's behavior, and some might be specified by the developers.
        $connection = $this->createConnection($dsn, $config, $options);

        if (! empty($config['database'])) {
            $connection->query("use `{$config['database']}`;");
        }

        $this->configureEncoding($connection, $config);

        // Next, we will check to see if a timezone has been specified in this config
        // and if it has we will issue a statement to modify the timezone with the
        // database. Setting this DB timezone is an optional configuration item.
        $this->configureTimezone($connection, $config);

        $this->setModes($connection, $config);
        return $connection;
    }

    protected function createPdoConnection($dsn, $username, $password, $options)
    {
        $pdoMysql = new PDO(...func_get_args());
        return $pdoMysql;
    }

    protected function tryAgainIfCausedByLostConnection(Exception $e, $dsn, $username, $password, $options)
    {
        if ($this->causedByLostConnection($e) || Str::contains($e->getMessage(), 'is closed')) {
            return $this->createPdoConnection($dsn, $username, $password, $options);
        }

        throw $e;
    }
}