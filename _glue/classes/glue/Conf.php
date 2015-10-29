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
namespace glue;
use \Symfony\Component\Yaml\Yaml;

class Conf {
  static $CONF = array();
  static $DIRS = array();

  static function get($request) {
    $request = explode('/',$request);
    //try to load named file if it hasn't been loaded
    if (!key_exists($request[0],static::$CONF)) {
      static::load($request[0]);
    }
    //return the appropriate bit of the config array
    $return = static::$CONF;
    foreach ($request as $sub) {
      if (key_exists($sub,$return)) {
        $return = $return[$sub];
      }else {
        return false;
      }
    }
    return static::tidy($return);
  }
  static function tidy($request) {
    if (is_array($request)) {
      foreach ($request as $key => $value) {
        $request[$key] = static::tidy($value);
      }
    }else {
      $request = preg_replace_callback('/@@[a-z0-9\/\-_]+@@/i',function($matches){
        $return = str_replace('@@','',$matches[0]);
        $return = Conf::get($return);
        return $return;
      },$request);
    }
    return $request;
  }
  static function load ($file) {
    $file = preg_replace('/[^a-z0-9\-_]/i','',$file);
    $paths = static::$DIRS;
    foreach ($paths as $key => $path) {
      $paths[$key] .= '/' . $file . '.yaml';
    }
    foreach ($paths as $filename) {
      if (file_exists($filename)) {
        static::loadFile($filename);
      }
    }
  }
  private static function loadFile($filename) {
    $data = Yaml::parse(file_get_contents($filename));
    static::$CONF = array_replace_recursive(static::$CONF,$data);
  }
}
Conf::$CONF['GLUE_PATH'] = GLUE_PATH;
Conf::$CONF['SITE_PATH'] = SITE_PATH;
Conf::$DIRS = array(
  GLUE_PATH . '/config',
  SITE_PATH . '/config'
);
