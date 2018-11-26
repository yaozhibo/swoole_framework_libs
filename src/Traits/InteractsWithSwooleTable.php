<?php
/**
 * Created by PhpStorm.
 * User: 姚志博
 * Date: 2018/11/20
 * Time: 14:57
 */

namespace YSwoole\Traits;

use Swoole\Table;
use YSwoole\Tables\SwooleTable;

trait InteractsWithSwooleTable
{
    protected $table;

    protected function createTables()
    {
        $this->table = new SwooleTable();
        $this->registerTables();
    }

    protected function registerTables()
    {
        $tables = $this->container['config']->get('yswoole_http.tables', []);

        foreach ($tables as $key => $value) {
            $table = new Table($value['size']);
            $columns = $value['columns'] ?? [];
            foreach ($columns as $column) {
                if (isset($column['size'])) {
                    $table->column($column['name'], $column['type'], $column['size']);
                } else {
                    $table->column($column['name'], $column['type']);
                }
            }
            $table->create();

            $this->table->add($key, $table);
        }
    }

    protected function bindSwooleTable()
    {
        $this->app->singleton(SwooleTable::class, function () {
            return $this->table;
        });
        $this->app->alias(SwooleTable::class, 'swoole.table');
    }
}
