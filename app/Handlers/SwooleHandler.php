<?php

namespace App\Handlers;

class SwooleHandler
{

    public static $swoole;

    public function __construct ($swoole) {
        self::$swoole = $swoole;
    }

    public static function exists($swooleTable, $valueKey)
    {
        return self::$swoole->{$swooleTable}->exists($valueKey);
    }

    public static function doesExistValue($swooleTable, $valueKey, $value)
    {
        $doesExist = false;
        foreach (self::$swoole->{$swooleTable} as $key => $row) {
            if ($row[$valueKey] == $value) {
                $doesExist = true;
                break;
            }
        }

        return $doesExist;
    }

    public static function getValueFromKey($swooleTable, $valueKey, $value, $returnKeyValue)
    {
        $keyValue = null;
        foreach (self::$swoole->{$swooleTable} as $key => $row) {
            if ($row[$valueKey] == $value) {
                $keyValue = $row[$returnKeyValue];
                break;
            }
        }

        return $keyValue;
    }

    public static function getValue($swooleTable, $key)
    {
        return self::$swoole->{$swooleTable}->get($key);
    }

    public static function setValue($swooleTable, $key, $payload)
    {
        return self::$swoole->{$swooleTable}->set($key, $payload);
    }

    public static function remove($swooleTable, $key)
    {
        return self::$swoole->{$swooleTable}->del($key);
    }

    public static function setColumnValue($swooleTable, $key, $column, $value)
    {
        return self::$swoole->{$swooleTable}[$key][$column] = $value;
    }

    public static function table($swooleTable)
    {
        return self::$swoole->{$swooleTable};
    }

    public static function incCtr($swooleTable, $key)
    {
        self::$swoole->{$swooleTable}->incr($key, 'counter', 1);
    }

    public static function decCtr($swooleTable, $key)
    {
        self::$swoole->{$swooleTable}->decr($key, 'counter', 1);

        $ctr = self::$swoole->{$swooleTable}[$key]['counter'];

        if ($ctr <= 0) {
           self::$swoole->{$swooleTable}->del($key);
        }
    }
}
