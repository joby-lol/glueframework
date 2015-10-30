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

class CRUDderFormatter
{
    protected $conn;
    protected $config;
    
    public function __construct($conn, $config) {
        $this->conn = $conn;
        $this->config = $config;
    }
    public function get($data)
    {
        //TODO: implement this
        return $data;
    }
    public function set($data)
    {
        //TODO: implement this
        return $data;
    }
    public function quote($data) {
        return $this->conn->quote($data);
    }
}
