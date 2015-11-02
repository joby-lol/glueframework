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
namespace glueExtras;

use \glue\Conf;
use \glue\Route;
use \glue\Template;
use \Mni\FrontYAML\Parser;

class RouteTools
{
    public static function routeRedirects()
    {
        if ($filename = static::findFileForRoute(Route::requestUri(), Conf::get('glueExtras/RouteTools/content/path'), array('url'))) {
            $file = fopen($filename, 'r');
            $firstline = trim(fgets($file));
            fclose($file);
            header('Location: ' . $firstline);
            Template:rawOutput();
        }
    }
    public static function routeMarkdown()
    {
        if ($filename = static::findFileForRoute(Route::requestUri(), Conf::get('glueExtras/RouteTools/content/path'), array('md'))) {
            $parser = new Parser();
            if (!($document = $parser->parse(file_get_contents($filename)))) {
                echo "<div><strong>Error:</strong> Failed to parse markdown/front-YAML</div>";
                return;
            }
            Route::any(Route::requestUri(), function () use ($document) {
                Template::setMulti($document->getYAML());
                echo $document->getContent();
            });
        }
    }
    public static function routeStatic()
    {
        //This function should die as soon as it handles a file, because that way you skip templating
        $filename = Conf::get('glueExtras/RouteTools/content/path') . Route::requestUri();
        $extension = explode('.', $filename);
        $extension = array_pop($extension);
        if (array_key_exists($extension, Conf::get('glueExtras/RouteTools/staticExtensions'))) {
            if (Conf::get('glueExtras/RouteTools/staticExtensions')[$extension] && is_file($filename)) {
                header("Content-Type: " . Conf::get('glueExtras/RouteTools/staticExtensions')[$extension]);
                Template::rawOutput(file_get_contents($filename));
            }
        }
    }
    public static function routeCodepages()
    {
        $filename = static::findFileForRoute(Route::requestUri(), Conf::get('glueExtras/RouteTools/codepages/path'), array('php'));
        if ($filename) {
            Route::any(Route::requestUri(), function() use ($filename) {
                require($filename);
            });
        }
    }
    public static function routeAutoRoute()
    {
        $class = explode('/', Route::requestUri());
        $class = strtolower($class[1]);
        $class = preg_replace('/[^a-z0-9_\-]/i', '', $class);
        $class = preg_replace('/\-+/', ' ', $class);
        $class = ucwords($class);
        $class = preg_replace('/ +/', '', $class);
        $class = '\\AutoRoute\\' . $class;
        if (class_exists($class, true)) {
            if (method_exists($class, 'main')) {
                Route::any(Route::requestUri().'(/.*)?', $class . '::main');
            }
        }
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
