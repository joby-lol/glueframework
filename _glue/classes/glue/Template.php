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

use \Twig_Loader_Filesystem;
use \Twig_Environment;

class Template
{
    private static $template = 'default.twig';
    public static $rawOutputActive = false;
    public static $rawOutputContent = '';
    private static $fields = array();
    private static $twigLoader = false;
    private static $twigEnv = false;

    public static function set($key,$value)
    {
        static::$fields[$key] = $value;
    }
    public static function setMulti($fields)
    {
        if (is_array($fields)) {
            foreach ($fields as $key => $value) {
                static::set($key,$value);
            }
        }
    }
    public static function rawOutput($out = "")
    {
        static::$rawOutputContent = $out;
        static::$rawOutputActive = true;
    }
    public static function setTemplate($template)
    {
        static::$template = $template;
    }
    public static function getTemplate()
    {
        return static::$template;
    }
    public static function getFields()
    {
        return array_replace_recursive(
        Conf::get('Template/fields'),
            static::$fields
        );
    }
    public static function render($tName = false, $values = false) {
        if (!$tName) {
            $tName = static::getTemplate();
        }
        if (!$values) {
            $values = static::getFields();
        }
        if (!static::$twigLoader) {
            static::$twigLoader = new Twig_Loader_Filesystem(array_reverse(Conf::get('Template/dirs')));
        }
        if (!static::$twigEnv) {
            static::$twigEnv = new Twig_Environment(static::$twigLoader, array());
        }
        $template = static::$twigEnv->loadTemplate(Template::getTemplate());
        echo $template->render(Template::getFields());
    }
}
