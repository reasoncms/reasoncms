#Core and Local Code

In Reason it is best practice to keep local configurations, templates, modules, and other custom code separate from core code. Reason provides parallel core and local directories for this purpose.

There are three places where local and core code exist in parallel structures in Reason:

- [PHP Core/Local Directories](#php-corelocal-directories)
- [Web Asset Core/Local Directories](#web-asset-corelocal-directories)
- [Settings Core/Local Directories](#settings-corelocal-directories)

##PHP core/local directories

Reason's core PHP code lives at `reason_4.0/lib/core/`.

There is a parallel directory for local custom code at `reason_4.0/lib/local/`.

When Reason PHP is included, it is included using custom include functions:
- `reason_include()`
- `reason_require()`
- `reason_include_once()`
- `reason_require_once()`

These functions check the local directory first, then fall back to the core. While practically this can allow any given file to be locally replaced wholesale, it's best to refrain from this practice to avoid potential errors and conflicts on upgrade.

Best practice is to add additional files (rather than replacing core files). For example a local content manager would be placed at `reason_4.0/lib/local/content_managers/` and a local template at `reason_4.0/lib/local/minisite_templates/`.

##Web Asset Core/Local Directories

For Javascript, CSS, images, raw PHP scripts, etc., core files are in `reason_4.0/www`.

The parallel local directory is `reason_4.0/www/local`.

There is an Apache .htaccess file in www/ that enables files at reason/www/foobar/ and reason/www/local/foobar/ to appear as if they are both at reason/www/foobar/. If there is a file name conflict, the local directory "wins".

## Settings Core/Local Directories

For application settings, core settings reside in `settings/`. Local settings can be managed in one of two ways:

###The files in `settings/` can be modified directly

Advantage:
- Upgrades via Git will automatically pull in new settings

Disadvantages:
- If Reason's default settings change or are reorganized it will cause headaches on upgrade as you attempt to reconcile your local changes with changes coming from the core
- If a core default that you haven't modified is changed, it will silently change your local setting upon upgrade.

###You can make a copy of `settings/` to `settings_local/`

If the `settings_local/` directory exists, Reason will use it instead. 

Advantages:
- A clear separation between your local settings and any changes that come in from the core on upgrade
- No surprises on upgrade

Disadvantages:
- If new settings are added in the core, you will need to manually add them locally
- Will need to manually change defaults if you want to adopt core default changes
