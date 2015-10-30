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
    public function __set($key, $val);
}
