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

    public function __construct($conn, $config)
    {
        $this->conn = &$conn;
        $this->config = $config;
    }
    public function get($field, $data)
    {
        $type = $this->config['fields'][$field]['type'];
        switch ($type) {
            case 'string':
                $data = strval($data);
                break;
            case 'int':
                $data = intval($data);
                break;
            case 'float':
                $data = floatval($data);
                break;
            case 'bool':
                $data = boolval($data);
                break;
            case 'timestamp':
                $data = intval($data);
                break;
            case 'datetime':
                $fmt = 'c';
                $tz = new \DateTimeZone('UTC');
                if (isset($this->config['fields'][$field]['format'])) {
                    $fmt = $this->config['fields'][$field]['format'];
                }
                if (isset($this->config['fields'][$field]['timezone'])) {
                    $tz = new \DateTimeZone($this->config['fields'][$field]['timezone']);
                }
                switch ($fmt) {
                    case 'c':
                        $data = date_create($data,$tz);
                        break;
                    default:
                        $data = date_create_from_format($fmt,$data,$tz);
                }
                break;
            default:
                $data = strval($data);
        }
        return $data;
    }
    public function set($field, $data)
    {
        $data = $this->forceToString($field, $data);
        return $data;
    }
    protected function forceToString($field, $data)
    {
        if ($data instanceof \DateTime) {
            $fmt = 'c';
            $tz = new \DateTimeZone('UTC');
            if (isset($this->config['fields'][$field]['format'])) {
                $fmt = $this->config['fields'][$field]['format'];
            }
            if (isset($this->config['fields'][$field]['timezone'])) {
                $tz = new \DateTimeZone($this->config['fields'][$field]['timezone']);
            }
            $data->setTimezone($tz);
            return $data->format($fmt);
        }
        return $data;
    }
}
