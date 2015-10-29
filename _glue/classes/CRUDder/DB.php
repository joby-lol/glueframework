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
namespace CRUDder;
class DB {
  private static $CONNS = array();
  /**
   * throw an exception if anybody tries to instantiate -- this class
   * is meant to be strictly static
   */
  function __construct() {
    throw new Exception(get_class() . " is not allowed to be instantiated!", 1);
  }
  /**
   * Get a reference to a PDO object connected to the given DB.
   * Tries to maintain only one connection to each database.
   * @param  string $DB_DSN      connection string
   * @param  string $DB_USERNAME username
   * @param  string $DB_PASSWORD password
   * @return PDO              reference to PDO object
   */
  static function &getConnection ($DB_DSN, $DB_USERNAME, $DB_PASSWORD) {
    $connID = md5($DB_DSN . $DB_USERNAME . $DB_PASSWORD);
    if (!key_exists($connID, static::$CONNS)) {
      static::$CONNS[$connID] = new \PDO($DB_DSN, $DB_USERNAME, $DB_PASSWORD);
      static::$CONNS[$connID]->setAttribute(\PDO::ATTR_PERSISTENT, TRUE);
      static::$CONNS[$connID]->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }
    return static::$CONNS[$connID];
  }
}
