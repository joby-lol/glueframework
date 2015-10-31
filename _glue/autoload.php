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

/**
 * Load composer autoloaders if necessary
 */
function glue_include_once_if_exists($file)
{
    if (file_exists($file)) {
        include_once($file);
    }
}

/**
 * PSR-4 autoloader, will search first in SITE_PATH/classes, then in
 * GLUE_PATH/classes
 * @return boolean whether or not class was found
 */
function glue_autoload($class)
{
    $paths = array();
    $paths[] = SITE_PATH . '/classes';
    $paths[] = GLUE_PATH . '/classes';
    $classfile = str_replace('\\', '/', $class);
    $classfile .= '.php';
    foreach ($paths as $path) {
        $filename = $path . '/' . $classfile;
        if (file_exists($filename)) {
            include_once $filename;
            if (class_exists($class) || interface_exists($class)) {
                return true;
            }
        }
    }
    return false;
}
spl_autoload_register('\glue\glue_autoload');
