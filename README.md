# Glue
A PHP framework for sticking the old to the new.

[![Build Status](https://travis-ci.org/jobyone/glueframework.svg?branch=initial-development)](https://travis-ci.org/jobyone/glueframework)
[![Code Climate](https://codeclimate.com/github/jobyone/glueframework/badges/gpa.svg)](https://codeclimate.com/github/jobyone/glueframework)
[![Test Coverage](https://codeclimate.com/github/jobyone/glueframework/badges/coverage.svg)](https://codeclimate.com/github/jobyone/glueframework/coverage)

Glue is a lightweight PHP framework designed for those who aren't building a new site from scratch. More often than not you can't just throw away all the code you've got on a website. Maybe you don't have the manpower, maybe you don't have the time. Maybe you realize that if it works you should probably just let it continue to work until you're good and ready to replace it.

Incremental improvements are the reason Glue exists. It's designed to offer a modern classloader, router, and templating system -- plus some management of configuration and credentials. All tied into tools for integrating it all together with various legacy anti-patterns.

## Installing
0. Place your `_glue` directory somewhere. Preferably above your web root so it can't possibly be browsed.
0. Create a new directory somewhere else to hold your site-specific code and configuration. You shouldn't ever need to touch the core `_glue` directory. You can copy `_glue-site` as a starting point.
0. Place `_glue.php` somewhere in your web directory. The root is good. Fill out its configuration options at the top.
0. Modify your .htaccess to route requests for nonexistent files to `_glue.php`. See `_glue/sample.htaccess` for more information.

## Usage

The following assumes your site's configuration folder is named `_glue-site`

### Configuring your site

Edit `_glue-site\config\glue.yaml` to set up the basics. You can load files from this directory by their name using `\glue\Conf::get([filename/path/to/option])`

Note that config options are cached per-request, and changing the config files at runtime (gross, don't do that) will not change what is returned by `Conf::get()`.

### Built in tools

#### CRUD classes

Glue has a built-in OOP CRUD tool called CRUDder.

## Writing your code

Code for your site should be placed in `_glue-site/classes` and `_glue-site/routes`.

### Classes

Anything in `_glue-site/classes` will be loaded by the autoloader. You should definitely namespace your code to avoid collisions. For example, place a class called `MyClass` in the file `_glue-site/classes/mynamespace/MyClass.php` and declare `namespace mynamespace;` at the top of the file. If you haven't used namespaces in PHP before, check out [the documentation](http://php.net/manual/en/language.namespaces.php).

### Routes

Which code handles which URLs is handled by the class `\glue\Route`.
The class contains static methods for specifying the handling of GET and POST requests. Note that all paths for Route should have a leading slash, and do support regular expressions. Any pieces of the regular expression grouped with parentheses will be passed to the handler function.

`\glue\Route::get(string $path, function $handler)`
Handles GET requests to the path matching `$path` with the function `$handler`.

`\glue\Route::post(string $path, function $handler)`
Handles POST requests to the path matching `$path` with the function `$handler`.

`\glue\Route::any(string $path, function $handler)`
Handles any requests to the path matching `$path` with the function `$handler`.
