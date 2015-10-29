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

class Template {
  private static $FIELDS = array();
  static $TEMPLATE = 'default';
  private static $TEMPLATE_FILE = false;
  static function set($key,$value) {
    static::$FIELDS[$key] = $value;
  }
  static function setBody($value) {
    static::set('pageBody',$value);
  }
  static function setTemplate() {

  }
  static function getTemplate() {
    foreach (array_reverse(Conf::get('Template/dirs')) as $path) {
      foreach (array_reverse(Conf::get('Template/extensions')) as $extension) {
        $filename = $path . '/' . static::$TEMPLATE . '.' . $extension;
        if (file_exists($filename)) {
          return file_get_contents($filename);
        }
      }
    }
    return static::$FALLBACK_TEMPLATE;
  }
  static function getFields() {
    return array_replace_recursive(
      Conf::get('Template/fields'),
      static::$FIELDS
    );
  }
  private static $FALLBACK_TEMPLATE = "<!doctype html>
<html>
<head>
<title>{{pageTitle}} :: {{siteTitle}}</title>
</head>
<body>
<h1>{{pageTitle}}</h1>
{{{pageBody}}}
<hr/>
<em>This is the Glue Framework last-resort template. If it is displaying, that means
that any requested template could not be found, and a default template could not
be loaded.</em>
</body>
</html>";
}
Template::$TEMPLATE = Conf::get('Template/defaultTemplate');
