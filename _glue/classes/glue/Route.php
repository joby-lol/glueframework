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
use \glue\Template;
use \Mni\FrontYAML\Parser;
use \Nanite;

class Route {
  static function get($url,$handler) {
    Nanite::get($url,$handler);
  }
  static function post($url,$handler) {
    Nanite::get($url,$handler);
  }
  static function any($url,$handler) {
    Nanite::get($url,$handler);
    Nanite::post($url,$handler);
  }
  static function loadfile($file) {
    include_once $file;
  }
  static function processed() {
    return Nanite::$routeProccessed;
  }
  static function routeRedirects() {
    if ($filename = static::findFileForRoute(Nanite::requestUri(),Conf::get('Route/content/path'),array('url'))) {
      $file = fopen($filename,'r');
      $firstline = trim(fgets($file));
      fclose($file);
      header('Location: ' . $firstline);
    }
  }
  static function routeMarkdown() {
    if ($filename = static::findFileForRoute(Nanite::requestUri(),Conf::get('Route/content/path'),array('md'))) {
      $parser = new Parser();
      if (!($document = $parser->parse(file_get_contents($filename)))) {
        echo "<div><strong>Error:</strong> Failed to parse markdown/front-YAML</div>";
      }else {
        Template::setMulti($document->getYAML());
        echo $document->getContent();
      }
    }
  }
  static function routeStatic() {
    //This function should die as soon as it handles a file, because that way you skip templating
    die('TODO: route static files');
  }
  static function routeCodepages() {
    if ($filename = static::findFileForRoute(Nanite::requestUri(),Conf::get('Route/codepages/path'),array('php'))) {
      glue_route_include($filename);
    }
  }
  static function routeAutoRoute() {
    $class = explode('/',Nanite::requestUri());
    $class = strtolower($class[1]);
    $class = preg_replace('/[^a-z0-9_\-]/i','',$class);
    $class = preg_replace('/\-+/',' ',$class);
    $class = ucwords($class);
    $class = preg_replace('/ +/','',$class);
    $class = '\\AutoRoute\\' . $class;
    if (class_exists($class,true)) {
      if (method_exists($class,'main')) {
        static::any(Nanite::requestUri().'(/.*)?',$class . '::main');
      }
    }
  }
  protected static function findFileForRoute($uri,$dir,$extensions) {
    if ($uri == '/') $uri = '';
    foreach ($extensions as $ext) {
      $possibilities = array();
      $possibilities[] = $dir . $uri . '.' . $ext;
      $possibilities[] = $dir . $uri . '/index.' . $ext;
      foreach($possibilities as $file) {
        if (file_exists($file)) {
          return $file;
        }
      }
    }
  }
}

/**
 * function used by Route to include codepages in a sterile environment
 */
function glue_route_include($file) {
  include($file);
}

/**
 * This function is used in glue.php to load routes
 */
function glue_load_routes() {
  //routes consist of Route/routes with Route/system appended
  $routes = Conf::get('Route/routes');
  $sysroutes = array_reverse(Conf::get('Route/system'));
  foreach($sysroutes as $route) {
    $routes[] = $route;
  }
  //Site routes can override system routes
  foreach ($routes as $value) {
    $paths = array(
      SITE_PATH . '/routes/' . $value . '.php',
      GLUE_PATH . '/routes/' . $value . '.php'
    );
    foreach ($paths as $file) {
      if (file_exists($file)) {
        Route::loadfile($file);
        break;
      }
    }
  }
}
