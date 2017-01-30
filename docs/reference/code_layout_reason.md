# Reason CMS Code Layout (e.g. reason_4.0/)

Directory | Purpose
------------ | -------------
lib/ | Reason PHP libraries
lib/core/ | Core Reason PHP code
lib/core/admin/ | Bootstrapper for the Reason administrative interface
lib/core/classes/ | Reason utility classes & OO API
lib/core/config/ | Default configurations; amend in ../local/config/
lib/core/content_deleters/ | Entity expungement code (hard deletion)
lib/core/content_listers | Administrative interface list views
lib/core/content_managers | Administrative interface edit views
lib/core/content_post_deleters | Code to run when entities are soft-deleted
lib/core/content_previewers | Administrative interface "preview" views
lib/core/content_sorters | Administrative interface tools for sorting entities
lib/core/display_name_handlers | Code to generate entity "display names"
lib/core/errors | Default error pages
lib/core/feeds | Framework for RSS feed generation
lib/core/finish_actions | Code to run upon completion of entity editing
lib/core/function_libraries | Reason utility functions & procedural API
lib/core/helpers | Type-sepcific utility classes – Deprecated directory
lib/core/html_editors | Shims for wysiwyg/html editors
lib/core/minisite | Bootstrapper for the Reason front-end interface
lib/core/minisite_templates | Reason front-end templates, modules, & page types
lib/core/popup_templates | Templates for popup windows
lib/core/popups | Bootstrappers for popup windows
lib/core/scripts | Loose utility scripts, webhooks, upgrade scripts, and other web-accessible tools & utilities not wrapped up or generalized into the front-end or back-end frameworks
lib/core/ssh | SSH/SCP libraries
lib/core/theme_customizers | Objects that encapsulate theme options
lib/local | Area for [local customizations & enhancements](core_local.md)
lib/local/* | local directories, parallel to `../core/`

* data *(File-based data storage)*
	* assets *(Documents uploaded to Reason)*
	* cache *(Ephemeral cached data that can be regenerated as needed)*
	* csv_data \*
	* dbs *(Database seeds & snapshots)*
	* geocodes *(Address -> Geocode cache)*
	* images *(Images uploaded to Reason, plus standard resized derivatives)*
	* logs *(Activity logs)*
	* media \*
	* sized_images *(Programatically cropped/resized image cache; can be regenerated as needed)*
	* sized_images_custom *(Manually cropped/resized image derivatives; *cannot* be regenerated as needed)*
	* tmp *(Default temp directory)*
	* www_tmp *(Temp directory for files that shoud be temporarily web-available)*
* hooks \*
* www *(Web-available assets)*
	* local *(Local additions)*
		* (local directories, parallel to `../`)
	* css *(General/reusable css)*
	* js *(General/reusable js)*
	* modules *(Module-specific css/js/images)*
	* images, sized_images, sized_images_custom *(symlink to equivalent directories in `data/`)*
	* tmp *(symlink to `data/www_tmp/`)*
	* etc.

\* Author unclear on purpose
