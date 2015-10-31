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
namespace glue\routes\glue_system;
use glue\Conf;
use glue\Route;
use glue\Template;
use glueExtras\RouteTools;

if (!Route::processed()) {
    if (Conf::get('glueExtras/RouteTools/content/enabled')) {
        RouteTools::routeRedirects();
        RouteTools::routeMarkdown();
        RouteTools::routeStatic();
    }
}
if (!Route::processed()) {
    if (Conf::get('glueExtras/RouteTools/codepages/enabled')) {
        RouteTools::routeCodepages();
    }
}
if (!Route::processed()) {
    if (Conf::get('glueExtras/RouteTools/autoroute/enabled')) {
        RouteTools::routeAutoRoute();
    }
}

//Warn if GLUE_PATH or SITE_PATH are in the document root
if (!Conf::get('environment/suppress_public_path_warnings')) {
    $nDOC_ROOT = str_replace('\\','/', $_SERVER['DOCUMENT_ROOT']);
    $nGLUE_PATH = str_replace('\\','/', GLUE_PATH);
    $nSITE_PATH = str_replace('\\','/', SITE_PATH);
    if (strpos($nGLUE_PATH,$nDOC_ROOT) === 0) {
        echo "<div><strong>Warning:</strong> Your GLUE_PATH is inside the document root.
            It's protected by a .htaccess file, but best practice is to move it outside your public web directory.
            <br/>You can suppress this warning by setting <em>suppress_public_path_warnings: true</em>
            in environment.yaml.</div>";
    }
    if (strpos($nSITE_PATH,$nDOC_ROOT) === 0) {
        echo "<div><strong>Warning:</strong> Your SITE_PATH is inside the document root.
            It's protected by a .htaccess file, but best practice is to move it outside your public web directory.
            <br/>You can suppress this warning by setting <em>suppress_public_path_warnings: true</em> in
            environment.yaml.</div>";
    }
}
