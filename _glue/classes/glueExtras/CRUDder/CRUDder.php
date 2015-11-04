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

use \Symfony\Component\Yaml\Yaml;

abstract class CRUDder implements CRUDderI
{
    protected $data = array();
    protected $dataChanged = array();
    protected $delete = false;

    protected static $config = array();
    protected static $conn;
    protected static $formatter;

    //Configuration methods
    public static function configureClass($yaml)
    {
        $newConfig = Yaml::parse($yaml);
        $class = get_called_class();
        $class::$config = array_replace_recursive($class::$config, $newConfig);
        $class::$formatter = new CRUDderFormatter(static::$conn, $class::$config);
    }
    public static function configureDB($dsn, $username, $password)
    {
        $class = get_called_class();
     static::$conn = DB::getConnection($dsn, $username, $password);
    }
    //Constructor -- protected so it can only be called by factories
    protected function __construct($input)
    {
        $class = get_called_class();
        foreach ($class::$config['fields'] as $fieldID => $fieldInfo) {
            $this->data[$fieldID] = $class::$formatter->get($fieldID, $input[$fieldInfo['col']]);
        }
    }
    //CRUD methods
    public static function create($data)
    {
        $class = get_called_class();
        $cols = array();
        $values = array();
        $valueKeys = array();
        foreach ($class::$config['fields'] as $fieldName => $fieldInfo) {
            if ($fieldName != $class::$config['key'] && isset($data[$fieldName])) {
                $cols[] = $fieldInfo['col'];
                $values[$fieldName] = $class::$formatter->set($fieldName, $data[$fieldName]);
                $valueKeys[] = $fieldName;
            }
        }
        //build query as a string
        $query = 'INSERT INTO ' . $class::$config['table'] . PHP_EOL;
        $query .= '       (' . implode(', ', $cols) . ')' . PHP_EOL;
        $query .= 'VALUES (:' . implode(', :', $valueKeys) . ')';
        $statement = static::$conn->prepare($query);
        if (!$statement->execute($values)) {
            return false;
        }
        $cKey = static::$conn->lastInsertId();
        return $class::read($cKey);
    }
    public static function query($options, $values)
    {
        $class = get_called_class();
        $defaults = array(
            'where'=>false,
            'sort'=>$class::$config['defaultSort'],
            'limit'=>0,
            'offset'=>0,
        );
        $options = array_replace_recursive($defaults, $options);
        //build query as a string
        //SELECT FROM statement
        $query = 'SELECT ';
        $cols = array();
        foreach ($class::$config['fields'] as $fieldInfo) {
            $cols[] = $fieldInfo['col'];
        }
        $query .= implode(', ', $cols) . PHP_EOL;
        $query .= 'FROM ' . $class::$config['table'] . PHP_EOL;
        //WHERE statement
        if ($options['where']) {
            $query .= 'WHERE' . PHP_EOL;
            $query .= $options['where'] . PHP_EOL;
        }
        //ORDER BY statement
        if ($options['sort']) {
            $query .= 'ORDER BY' . PHP_EOL;
            $query .= $options['sort'] . PHP_EOL;
        }
        //LIMIT/OFFSET statement
        if ($options['limit']) {
            $query .= 'LIMIT ' . $options['limit'] .PHP_EOL;
        }
        if ($options['offset']) {
            $query .= 'OFFSET ' . $options['offset'] .PHP_EOL;
        }
        //Fix column names
        $query = $class::queryColNameFormatter($query);
        //Retrieve result
        $statement = static::$conn->prepare($query);
        if ($statement->execute($values) === false) {
            return false;
        }
        $results = $statement->fetchAll();
        $objects = array();
        foreach ($results as $row) {
            $objects[] = new $class($row);
        }
        return $objects;
    }
    public static function read($key)
    {
        $class = get_called_class();
        $result = $class::query(
            array(//query
                'sort' => false,
                'where' => '@@' . $class::$config['key'] . '@@ = :' . $class::$config['key'],
                'limit' => 1
            ),
            array(//values
                $class::$config['key'] => $key
            )
        );
        if (count($result) == 0) {
            return false;
        }
        return $result[0];
    }
    public function update()
    {
        if (count($this->dataChanged) === 0 || $this->deleted) {
            return;
        }
        $class = get_called_class();
        $updates = array();
        $values = array(
            $class::$config['key'] => $this->__get($class::$config['key'])
        );
        foreach (array_keys($this->dataChanged) as $fieldName) {
            if ($fieldName != $class::$config['key']) {
                $updates[] = '@@' . $fieldName . '@@ = :' . $fieldName;
                $values[] = $class::$formatter->set($fieldName, $this->data[$fieldName]);
            }
        }
        //build query as a string
        $query = 'UPDATE ' . $class::$config['table'] . PHP_EOL;
        $query .= 'SET ' . implode(', ',$updates) . PHP_EOL;
        $query .= 'WHERE @@' . $class::$config['key'] . '@@ = :' . $class::$config['key'] . PHP_EOL;
        //$query .= 'LIMIT 1';
        //Fix column names
        $query = $class::queryColNameFormatter($query);
        //Execute query
        $statement = static::$conn->prepare($query);
        $result = $statement->execute($values);
        if ($result) {
            $this->dataChanged = array();
        }
        return $result;
    }
    public function delete()
    {
        if ($this->deleted) {
            return;
        }
        $class = get_called_class();
        $values = array(
            $class::$config['key'] => $this->__get($class::$config['key'])
        );
        //build query
        $query = 'DELETE FROM ' . $class::$config['table'] . PHP_EOL;
        $query .= 'WHERE @@' . $class::$config['key'] . '@@ = :' . $class::$config['key'] . PHP_EOL;
        //Fix column names
        $query = $class::queryColNameFormatter($query);
        //Execute query
        $statement = static::$conn->prepare($query);
        $statement->execute($values);
    }
    //transactions
    public static function getTransacter()
    {
        $class = get_called_class();
        return new Transacter(static::$conn);
    }
    //getting and setting
    public function __get($key)
    {
        if (!array_key_exists($key,$this->data)) {
            return false;
        }
        return $this->data[$key];
    }
    public function __set($key, $val)
    {
        $class = get_called_class();
        $this->data[$key] = $class::$formatter->set($key, $val);
        $this->dataChanged[$key] = $val;
    }
    //internal utility functions
    public static function getConfig()
    {
        $class = get_called_class();
        return $class::$config;
    }
    protected static function queryColNameFormatter($string)
    {
        $class = get_called_class();
        $string = preg_replace_callback('/@@([^@]+)@@/', function ($match) use ($class) {
            $col = $class::getConfig();
            $col = $col['fields'][$match[1]]['col'];
            if (!$col) {
                throw new \Exception("Couldn't find a field named " . $match[1], 1);
            }
            return $col;
        }, $string);
        return $string;
    }
}
