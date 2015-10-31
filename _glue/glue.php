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

use \Mustache_Engine;

ob_start();
define('PAGE_RENDER_START', microtime(TRUE));

// get autoloader
require GLUE_PATH . '/autoload.php';
glue_include_once_if_exists(GLUE_PATH . '/vendor/autoload.php');
glue_include_once_if_exists(SITE_PATH . '/vendor/autoload.php');
glue_autoload('\glue\Route');//must be explicitly loaded

// Load site-specific glue.php, between setup and final template output
require SITE_PATH . '/glue.php';

// Load routes
glue_load_routes();

// Timing page rendering
define('PAGE_RENDER_END', microtime(TRUE));
echo "<!-- Page took " . (PAGE_RENDER_END-PAGE_RENDER_START) . "s to generate -->";

// Drop output into Template and render with mustache engine
$body = ob_get_clean();
if (Template::$staticOutputActive) {
    echo Template::$staticOutputContent;
}else {
    Template::setBody($body);
    $m = new Mustache_Engine;
    echo $m->render(
      Template::getTemplate(),
      Template::getFields()
    );
}
