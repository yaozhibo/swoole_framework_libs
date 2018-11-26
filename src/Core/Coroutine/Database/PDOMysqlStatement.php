<?php
/**
 * Created by PhpStorm.
 * User: 姚志博
 * Date: 2018/11/22
 * Time: 15:41
 */
namespace YSwoole\Core\Coroutine\Database;

use PDOStatement;
use YSwoole\Core\Coroutine\Database\PDO;
use Swoole\Coroutine\Mysql\Statement;

class PDOMysqlStatement extends PDOStatement
{
    private $pdoMysql;
    public $statement;
    public $timeout;
    public $bindMap = [];
    public $cursor = -1;
    public $cursorOrientation = PDO::FETCH_ORI_NEXT;
    public $resultSet = [];
    public $fetchStyle = PDO::FETCH_BOTH;

    public function __construct(PDO $pdoMysql, Statement $statement, array $driverConfigs = [])
    {
        $this->pdoMysql = $pdoMysql;
        $this->statement = $statement;
        $this->timeout = $driverConfigs['timeout'] ?? -1;
    }


    public function errorCode()
    {
        return $this->statement->errno;
    }

    public function errorInfo()
    {
        return $this->statement->errno;
    }

    public function rowCount()
    {
        return $this->statement->affected_rows;
    }

    public function bindParam($param, &$variable, $type = null, $maxlen = null, $driverData = null)
    {
        if (! is_string($param) && ! is_int($param)) {
            return false;
        }

        $param = ltrim($param, ':');
        $this->bindMap[$param] = &$variable;

        return true;
    }

    public function bindValue($param, $variable, $type = null)
    {
        if (! is_string($param) && ! is_int($param)) {
            return false;
        }

        if (is_object($variable)) {
            if (! method_exists($variable, '__toString')) {
                return false;
            } else {
                $variable = (string) $variable;
            }
        }

        $param = ltrim($param, ':');
        $this->bindMap[$param] = $variable;

        return true;
    }

    private function afterExecute()
    {
        $this->cursor = -1;
        $this->bindMap = [];
    }

    public function execute($input_parameters = null)
    {
        if (! empty($input_parameters)) {
            foreach ($input_parameters as $key => $value) {
                $this->bindParam($key, $value);
            }
        }

        $input_parameters = [];
        if (! empty($this->statement->bindKeyMap)) {
            foreach ($this->statement->bindKeyMap as $nameKey => $numKey) {
                $inputParameters[$numKey] = $this->bindMap[$nameKey];
            }
        } else {
            $inputParameters = $this->bindMap;
        }

        $result = $this->statement->execute($inputParameters, $this->timeout);
        $this->resultSet = ($ok = $result !== false) ? $result : [];
        $this->afterExecute();
        return $ok;
    }

    public function setFetchMode($fetchStyle, $params = null)
    {
        $this->fetchStyle = $fetchStyle;
    }

    private function __executeWhenStringQueryEmpty()
    {
        if (is_string($this->statement) && empty($this->resultSet)) {
            $this->resultSet = $this->pdoMysql->client->query($this->statement);
            $this->afterExecute();
        }
    }

    private function transBoth($rawData)
    {
        $temp = [];
        foreach ($rawData as $row) {
            $rowSet = [];
            $i = 0;
            foreach ($row as $key => $value) {
                $rowSet[$key] = $value;
                $rowSet[$i++] = $value;
            }
            $temp[] = $rowSet;
        }

        return $temp;
    }

    private function transStyle(
        $rawData,
        $fetchStyle = null,
        $fetchArgument = null,
        $ctorArgs = null
    ) {
        if (! is_array($rawData)) {
            return false;
        }
        if (empty($rawData)) {
            return $rawData;
        }

        $fetchStyle = is_null($fetchStyle) ? $this->fetchStyle : $fetchStyle;
        $ctorArgs = is_null($ctorArgs) ? [] : $ctorArgs;

        $resultSet = [];
        switch ($fetchStyle) {
            case PDO::FETCH_BOTH:
                $resultSet = $this->transBoth($rawData);
                break;
            case PDO::FETCH_COLUMN:
                $resultSet = array_column(
                    is_numeric($fetchArgument) ? $this->transBoth($rawData) : $rawData,
                    $fetchArgument
                );
                break;
            case PDO::FETCH_OBJ:
                foreach ($rawData as $row) {
                    $resultSet[] = (object) $row;
                }
                break;
            case PDO::FETCH_NUM:
                foreach ($rawData as $row) {
                    $resultSet[] = array_values($row);
                }
                break;
            case PDO::FETCH_ASSOC:
            default:
                return $rawData;
        }
        return $resultSet;
    }

    public function fetch($fetchStyle = null, $cursorOrientation = null, $cursorOffset = null, $fetchArgument = null)
    {
        $this->__executeWhenStringQueryEmpty();

        $cursorOrientation = is_null($cursorOrientation) ? PDO::FETCH_ORI_NEXT : $cursorOrientation;
        $cursorOffset = is_null($cursorOffset) ? 0 : (int) $cursorOffset;

        switch ($cursorOrientation) {
            case PDO::FETCH_ORI_ABS:
                $this->cursor = $cursorOffset;
                break;
            case PDO::FETCH_ORI_REL:
                $this->cursor += $cursorOffset;
                break;
            case PDO::FETCH_ORI_NEXT:
            default:
                $this->cursor++;
        }

        if (isset($this->resultSet[$this->cursor])) {
            $result = $this->resultSet[$this->cursor];
            unset($this->resultSet[$this->cursor]);
        } else {
            $result = false;
        }

        if (empty($result)) {
            return $result;
        } else {
            return $this->transStyle([$result], $fetchStyle, $fetchArgument)[0];
        }
    }

    public function fetchColumn($columnNumber = null)
    {
        $columnNumber = is_null($columnNumber) ? 0 : $columnNumber;
        $this->__executeWhenStringQueryEmpty();
        return $this->fetch(PDO::FETCH_COLUMN, PDO::FETCH_ORI_NEXT, 0, $columnNumber);
    }

    public function fetchAll($fetchStyle = null, $fetchArgument = null, $ctorArgs = null)
    {
        $this->__executeWhenStringQueryEmpty();
        $resultSet = $this->transStyle($this->resultSet, $fetchStyle, $fetchArgument, $ctorArgs);
        $this->resultSet = [];
        return $resultSet;
    }
}