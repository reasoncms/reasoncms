#Core and Local Code

In Reason it is best practice to keep local configurations, templates, modules, and other custom code separate from core code. This is accomplished by establishing parallel core and local directories.

There are three places where local and core code exist in parallel structures in Reason:

##PHP core/local directories

Reason's core PHP code lives at `reason_4.0/lib/core/`.

There is a parallel directory for local custom code at `reason_4.0/lib/local/`.

When Reason PHP is included, it is included using custom include functions:
- `reason_include()`
- `reason_require()`
- `reason_include_once()`
- `reason_require_once()`

These functions check the local directory first, then fall back to the core. This allows you to maintain a 

##Web Asset Core/Local Directories

For Javascript, CSS, images, raw PHP scripts, etc., core files are in `reason_4.0/www`.

The parallel local directory is `reason_4.0/www/local`.

There is an Apache .htaccess file in www/ that enables files at reason/www/foobar/ and reason/www/local/foobar/ to appear as if they are both at reason/www/foobar/. If there is a file name conflict, the local directory "wins".
