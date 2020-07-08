<?php

namespace App\Handlers;

class SwooleHandler
{

    public static $swoole;

    public function __construct ($swoole) {
        self::$swoole = $swoole;
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

    public static function setColumnValue($swooleTable, $key, $column, $value)
    {
        return self::$swoole->{$swooleTable}[$key][$column] = $value;
    }
}
