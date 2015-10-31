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
class DB
{
    private static $conns = array();
    /**
    * throw an exception if anybody tries to instantiate -- this class
    * is meant to be strictly static
    */
    public function __construct()
    {
        throw new Exception(get_class() . " is not allowed to be instantiated!", 1);
    }
    /**
    * Get a reference to a PDO object connected to the given DB.
    * Tries to maintain only one connection to each database.
    * @param  string $dbDSN      connection string
    * @param  string $dbUsername username
    * @param  string $dbPassword password
    * @return PDO              reference to PDO object
    */
    public static function &getConnection ($dbDSN, $dbUsername, $dbPassword)
    {
        $connID = md5($dbDSN . $dbUsername . $dbPassword);
        if (!key_exists($connID, static::$conns)) {
            static::$conns[$connID] = new \PDO($dbDSN, $dbUsername, $dbPassword);
            static::$conns[$connID]->setAttribute(\PDO::ATTR_PERSISTENT, TRUE);
            static::$conns[$connID]->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
        return static::$conns[$connID];
    }
}
