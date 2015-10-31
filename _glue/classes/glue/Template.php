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

class Template
{
    public static $template = 'default';
    public static $rawOutputActive = false;
    public static $rawOutputContent = '';
    private static $fields = array();
    private static $fallbackTemplate = '{{{pageBody}}}\n<!-- Something is very wrong. -->';

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
    public static function setBody($value)
    {
        static::set('pageBody',$value);
    }
    public static function setTemplate($template)
    {
        static::$template = $template;
    }
    public static function getTemplate()
    {
        foreach (array_reverse(Conf::get('Template/dirs')) as $path) {
            foreach (array_reverse(Conf::get('Template/extensions')) as $extension) {
                $filename = $path . '/' . static::$template . '.' . $extension;
                if (file_exists($filename)) {
                    return file_get_contents($filename);
                }
            }
        }
        return static::$fallbackTemplate;
    }
    public static function getFields()
    {
        return array_replace_recursive(
        Conf::get('Template/fields'),
            static::$fields
        );
    }
    public static function setFallbackTemplate ($template)
    {
        if ($template) {
            static::$fallbackTemplate = $template;
        }
    }
}
Template::setFallbackTemplate(file_get_contents(__DIR__ . '/Template-fallback.mustache'));
Template::$template = Conf::get('Template/defaultTemplate');
