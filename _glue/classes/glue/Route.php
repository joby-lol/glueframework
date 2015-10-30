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

class Route
{
    public static function get($url, $handler)
    {
        Nanite::get($url, $handler);
    }
    public static function post($url, $handler)
    {
        Nanite::get($url, $handler);
    }
    public static function any($url, $handler)
    {
        Nanite::get($url, $handler);
        Nanite::post($url, $handler);
    }
    public static function loadfile($file)
    {
        include_once $file;
    }
    public static function processed()
    {
        return Nanite::$routeProccessed;
    }
    public static function routeRedirects()
    {
        if ($filename = static::findFileForRoute(static::requestUri(), Conf::get('Route/content/path'), array('url'))) {
            $file = fopen($filename, 'r');
            $firstline = trim(fgets($file));
            fclose($file);
            header('Location: ' . $firstline);
            die();
        }
    }
    public static function routeMarkdown()
    {
        if ($filename = static::findFileForRoute(static::requestUri(), Conf::get('Route/content/path'), array('md'))) {
            $parser = new Parser();
            if (!($document = $parser->parse(file_get_contents($filename)))) {
                echo "<div><strong>Error:</strong> Failed to parse markdown/front-YAML</div>";
                return;
            }
            static::any(static::requestUri(), function () use ($document) {
                Template::setMulti($document->getYAML());
                echo $document->getContent();
            });
        }
    }
    public static function routeStatic()
    {
        //This function should die as soon as it handles a file, because that way you skip templating
        $filename = Conf::get('Route/content/path') . static::requestUri();
        $extension = explode('.', $filename);
        $extension = array_pop($extension);
        if (array_key_exists($extension, Conf::get('Route/staticExtensions'))) {
            if (Conf::get('Route/staticExtensions')[$extension] && is_file($filename)) {
                header("Content-Type: " . Conf::get('Route/staticExtensions')[$extension]);
                die(file_get_contents($filename));
            }
        }
    }
    public static function routeCodepages()
    {
        $filename = static::findFileForRoute(static::requestUri(), Conf::get('Route/codepages/path'), array('php'));
        if ($filename) {
            static::any(static::requestUri(), function() use ($filename) {
                require($filename);
            });
        }
    }
    public static function routeAutoRoute()
    {
        $class = explode('/', static::requestUri());
        $class = strtolower($class[1]);
        $class = preg_replace('/[^a-z0-9_\-]/i', '', $class);
        $class = preg_replace('/\-+/', ' ', $class);
        $class = ucwords($class);
        $class = preg_replace('/ +/', '', $class);
        $class = '\\AutoRoute\\' . $class;
        if (class_exists($class, true)) {
            if (method_exists($class, 'main')) {
                static::any(static::requestUri().'(/.*)?', $class . '::main');
            }
        }
    }
    public static function requestUri()
    {
        return Nanite::requestUri();
    }
    protected static function findFileForRoute($uri, $dir, $extensions)
    {
        if ($uri == '/') {
            $uri = '';
        }
        foreach ($extensions as $ext) {
            $possibilities = array();
            $possibilities[] = $dir . $uri . '.' . $ext;
            $possibilities[] = $dir . $uri . '/index.' . $ext;
            foreach ($possibilities as $file) {
                if (file_exists($file)) {
                    return $file;
                }
            }
        }
    }
}

/**
 * This function is used in glue.php to load routes
 */
function glue_load_routes()
{
    //routes consist of Route/routes with Route/system appended
    $routes = Conf::get('Route/routes');
    $sysroutes = array_reverse(Conf::get('Route/system'));
    foreach ($sysroutes as $route) {
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
