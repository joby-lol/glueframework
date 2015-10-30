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
namespace glue\CRUDder;

use \Symfony\Component\Yaml\Yaml;

abstract class CRUDder implements CRUDderI
{
    protected $data = array();
    protected $dataChanged = array();

    protected static $config = array();
    protected static $conn;
    protected static $formatter;

    //Configuration methods
    public static function configureClass($yaml)
    {
        $newConfig = Yaml::parse($yaml);
        $class = get_called_class();
        $class::$config = array_replace_recursive($class::$config, $newConfig);
        $class::$formatter = new CRUDderFormatter($class::$conn, $class::$config);
    }
    public static function configureDB($dsn, $username, $password)
    {
        static::$conn = DB::getConnection($dsn, $username, $password);
    }
    //Constructor -- protected so it can only be called by factories
    protected function __construct($input)
    {
        $class = get_called_class();
        foreach ($class::$config['fields'] as $fieldID => $fieldInfo) {
            $this->data[$fieldID] = $input[$fieldInfo['col']];
        }
    }
    //CRUD methods
    public static function create($data)
    {
        $class = get_called_class();
        $cols = array();
        $values = array();
        foreach ($class::$config['fields'] as $fieldName => $fieldInfo) {
            if ($fieldName != $class::$config['key']) {
                $cols[] = $fieldInfo['col'];
                $values[] = static::$formatter->quote($data[$fieldName]);
            }
        }
        //build query as a string
        $query = 'INSERT INTO ' . $class::$cTable . PHP_EOL;
        $query .= '       (' . implode(', ', $cols) . ')' . PHP_EOL;
        $query .= 'VALUES (' . implode(', ', $values) . ')';
        $statement = static::$conn->prepare($query);
        if (!$statement->execute()) {
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
            'sort'=>$class::$cSort,
            'limit'=>0,
            'offset'=>0,
        );
        $options = array_replace_recursive($defaults,$options);
        //build query as a string
        //SELECT FROM statement
        $query = 'SELECT ';
        $cols = array();
        foreach ($class::$cFields as $fieldName => $fieldInfo) {
            $cols[] = $fieldInfo['col'];
        }
        $query .= implode(', ', $cols) . PHP_EOL;
        $query .= 'FROM ' . $class::$cTable . PHP_EOL;
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
        $statement = $class::$conn->prepare($query);
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
        }else {
            return $result[0];
        }
    }
    public function update()
    {
        die('TODO: implement update');
    }
    public function delete()
    {
        die('TODO: implement delete');
    }
    //transactions
    public static function transactionStart()
    {
        die('TODO: implement transactionStart');
    }
    public static function transactionCommit()
    {
        die('TODO: implement transactionCommit');
    }
    public static function transactionDiscard()
    {
        die('TODO: implement transactionDiscard');
    }
    //getting and setting
    public function __get($key)
    {
        if (!isset($this->data[$key])) {
            return false;
        }
        return static::$formatter->get(
            $this->data[$key],
            $this->config['fields'][$key],
            $this->conn
        );
    }
    public function __set($key, $val)
    {
        $this->data[$key] = static::$formatter->set(
            $this->data[$key],
            $this->config['fields'][$key],
            $this->conn
        );
        $this->dataChanged[$key] = true;
    }
    //internal utility functions
    public static function getConfig()
    {
        return static::$config;
    }
    protected static function queryColNameFormatter($string)
    {
        $class = get_called_class();
        $string = preg_replace_callback('/@@([^@]+)@@/',function($match) use ($class) {
            $col = $class::getConfig()['fields'][$match[1]]['col'];
            if ($col) {
                return $col;
            }else {
                throw new \Exception("Couldn't find a field named " . $match[1], 1);
            }
        },$string);
        return $string;
    }
}
