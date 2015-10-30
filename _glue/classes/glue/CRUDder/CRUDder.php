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

interface CRUDderI
{
    const FALSEVAL = false;
    // Configuration
    public static function configureClass($yaml);
    public static function configureDB($dsn, $username, $password);
    // Basic CRUD
    public static function create($data);
    public static function query($query);
    public static function read($key);
    public function update();
    public function delete();
    // transactions
    public static function transactionStart();
    public static function transactionCommit();
    public static function transactionDiscard();
    // get/set
    public function &__get($key);
    public function __set($key,$val);
}

abstract class CRUDder implements CRUDderI
{
    protected static $config = array();
    protected static $conn;
    //Configuration methods
    public static function configureClass($yaml)
    {
        $newConfig = Yaml::parse($yaml);
        $class = get_called_class();
        $class::$config = array_replace_recursive($class::$config, $newConfig);
    }
    public static function configureDB($dsn, $username, $password)
    {
        static::$conn = DB::getConnection($dsn, $username, $password);
    }
    //CRUD methods
    public static function create($data)
    {
        die('TODO: implement create');
    }
    public static function query($query)
    {
        die('TODO: implement query');
    }
    public static function read($key)
    {
        die('TODO: implement read');
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
    public function &__get($key)
    {
        die('TODO: implement getter');
    }
    public function __set($key,$val)
    {
        die('TODO: implement setter');
    }
}
