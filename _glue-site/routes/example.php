<?php
/**
  * Glue Framework
  * Copyright (C) 2015 Joby Elliott
  *
  * This file is not licensed along with the rest of the Glue Framework. It is
  * released into the public domain, and you are free to modify or re-use it
  * in any way.
 */
namespace glue\routes\glue_site;
use glue\Route;
use glue\Template;

Route::get('/example-route', function(){
    Template::set('pageTitle', 'Site page');
    echo "<div>glue site-specific routed page</div>";
});
