<?php
 /**
  * Glue Framework
  * Copyright (C) 2015 Joby Elliott
  *
  * This program is free software; you can redistribute it and/or modify
  * it under the terms of the GNU General Public License as published by
  * the Free Software Foundation; either version 2 of the License, or
  * (at your option) any later version.
  *
  * This program is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License along
  * with this program; if not, write to the Free Software Foundation, Inc.,
  * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */
namespace glueExtras\CRUDder;

class CRUDderFormatter
{
    protected $conn;
    protected $config;
    protected static $getHandlers = array();
    protected static $setHandlers = array();

    public function __construct($conn, $config)
    {
        $this->conn = &$conn;
        $this->config = $config;
    }
    public static function registerGetHandler($type,$handler) {
        if (function_exists($handler)) {
            static::$getHandlers[$type] = $handler;
        }
    }
    public static function registerSetHandler($type,$handler) {
        if (function_exists($handler)) {
            static::$setHandlers[$type] = $handler;
        }
    }
    public function get($field, $data) {
        $type = $this->config['fields'][$field]['type'];
        if (isset(static::$getHandlers[$type]) && function_exists(static::$getHandlers[$type])) {
            $handler = static::$getHandlers[$type];
            $data = $handler($data, $this->config['fields'][$field]);
        }
        return $data;
    }
    public function set($field, $data)
    {
        $type = $this->config['fields'][$field]['type'];
        if (isset(static::$setHandlers[$type]) && function_exists(static::$setHandlers[$type])) {
            $handler = static::$setHandlers[$type];
            $data = $handler($data, $this->config['fields'][$field]);
        }
        return strval($data);
    }
}

function formatter_string_get ($data, $fieldInfo)
{
    return strval($data);
}
function formatter_int_get ($data, $fieldInfo)
{
    return intval($data);
}
function formatter_float_get ($data, $fieldInfo)
{
    return floatval($data);
}
function formatter_bool_get ($data, $fieldInfo)
{
    if (function_exists('boolval')) {
        return boolval($data);
    }
    return settype($data, 'boolean');
}
function formatter_datetime_get ($data, $fieldInfo)
{
    $fmt = 'c';
    $tzDB = new \DateTimeZone('UTC');
    $tzOut = new \DateTimeZone('UTC');
    if (isset($fieldInfo['format'])) {
        $fmt = $fieldInfo['format'];
    }
    if (isset($fieldInfo['timezone_db'])) {
        $tzDB = new \DateTimeZone($fieldInfo['timezone_db']);
        $tzOut = new \DateTimeZone($fieldInfo['timezone_db']);
    }
    if (isset($fieldInfo['timezone'])) {
        $tzOut = new \DateTimeZone($fieldInfo['timezone']);
    }
    switch ($fmt) {
        case 'c':
            $data = date_create($data,$tzDB);
            break;
        default:
            $data = date_create_from_format($fmt,$data,$tzDB);
    }
    $data->setTimezone($tzOut);
    return $data;
}
function formatter_subObject_get ($data, $fieldInfo)
{
    $objectType = $fieldInfo['objectType'];
    if (!class_exists($objectType)) {
        throw new Exception("objectType \"$objectType\" isn't a class", 1);
    }
    return $objectType::create($data);
}
CRUDderFormatter::registerGetHandler('string','\glueExtras\CRUDder\formatter_string_get');
CRUDderFormatter::registerGetHandler('int','\glueExtras\CRUDder\formatter_int_get');
CRUDderFormatter::registerGetHandler('float','\glueExtras\CRUDder\formatter_float_get');
CRUDderFormatter::registerGetHandler('bool','\glueExtras\CRUDder\formatter_bool_get');
CRUDderFormatter::registerGetHandler('timestamp','\glueExtras\CRUDder\formatter_int_get');
CRUDderFormatter::registerGetHandler('datetime','\glueExtras\CRUDder\formatter_datetime_get');
CRUDderFormatter::registerGetHandler('subObject','\glueExtras\CRUDder\formatter_subObject_get');

function formatter_datetime_set ($data, $fieldInfo)
{
    if ($data instanceof \DateTime) {
        $fmt = 'c';
        $tzDB = new \DateTimeZone('UTC');
        if (isset($fieldInfo['format'])) {
            $fmt = $fieldInfo['format'];
        }
        if (isset($fieldInfo['timezone_db'])) {
            $tzDB = new \DateTimeZone($fieldInfo['timezone_db']);
        }
        $data->setTimezone($tzDB);
        return $data->format($fmt);
    }
    return $data;
}
function formatter_subObject_set ($data, $fieldInfo)
{
    if (!$data) {
        return null;
    }
    $keyField = $fieldInfo['keyField'];
    return $data->__get($keyField);
}
CRUDderFormatter::registerSetHandler('datetime','\glueExtras\CRUDder\formatter_datetime_set');
CRUDderFormatter::registerSetHandler('subObject','\glueExtras\CRUDder\formatter_subObject_set');
