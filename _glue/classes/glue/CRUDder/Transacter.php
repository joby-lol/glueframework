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

class Transacter
{
    protected $transactionID;
    protected static $conn;
    protected static $transactions = array();

    public function __construct($conn)
    {
        $this->transactionID = rand();
        static::$conn = $conn;
    }
    public function beginTransaction()
    {
        static::$transactions[] = $this->transactionID;
        return static::$conn->exec('SAVEPOINT trans' . count(static::$transactions)) >= 1;
    }
    public function rollback()
    {
        if (end(static::$transactions) != $this->transactionID) {
            return false;
        }
        array_pop(static::$transactions);
        return static::$conn->exec('ROLLBACK TO trans' . (count(static::$transactions)+1)) >= 1;
    }
    public function commit()
    {
        if (end(static::$transactions) != $this->transactionID) {
            return false;
        }
        array_pop(static::$transactions);
        return count(static::$transactions) == 0;
    }
}
