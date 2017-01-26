# Reason CMS Code Layout (e.g. reason_4.0/)

* lib *(Reason PHP libraries)*
	* core *(Core Reason PHP code)*
		* admin *(Bootstrapper for the Reason administrative interface)*
		* classes *(Reason utility classes & OO API)*
		* config *(Default configurations; amend in ../local/config/)*
		* content_deleters *(Entity expungement code – hard deletion)*
		* content_listers *(Administrative interface list views)*
		* content_managers *(Administrative interface edit views)*
		* content_post_deleters *(Code to run when entities are soft-deleted)*
		* content_previewers *(Administrative interface "preview" views)*
		* content_sorters *(Administrative interface tools for sorting entities)*
		* display_name_handlers *(Code to generate entity "display names")*
		* errors *(Default error pages)*
		* feeds *(Framework for generating RSS feeds)*
		* finish_actions *(Code to run upon completion of entity editing)*
		* function_libraries *(Reason utility functions & procedural API)*
		* helpers *(Type-sepcific utility classes – Deprecated directory)*
		* html_editors *(Shims for wysiwyg/html editors)*
		* minisite *(Bootstrapper for the Reason front-end interface)*
		* minisite_templates *(Reason front-end templates, modules, & page types)*
		* popup_templates *(Templates for popup windows)*
		* popups *(Bootstrappers for popup windows)*
		* scripts *(Loose utility scripts, webhooks, upgrade scripts, and other web-accessible tools & utilities not wrapped up or generalized into the front-end or back-end frameworks)*
		* ssh *(SSH/SCP libraries)*
		* theme_customizers *(Objects that encapsulate theme options)*
	* local *Area for [local customizations & enhancements](core_local.md)*
		* (local directories, parallel to `../core/`)
* data *(File-based data storage)*
	* assets *(Documents uploaded to Reason)*
	* cache *(Ephemeral cached data that can be regenerated as needed)*
	* csv_data *(???)*
	* dbs *(Database seeds & snapshots)*
	* geocodes *(Address -> Geocode cache)*
	* images *(Images uploaded to Reason, plus standard resized derivatives)*
	* logs *(Activity logs)*
	* media *(???)*
	* sized_images *(Programatically cropped/resized image cache; can be regenerated as needed)*
	* sized_images_custom *(Manually cropped/resized image derivatives; *cannot* be regenerated as needed)*
	* tmp *(Default temp directory)*
	* www_tmp *(Temp directory for files that shoud be temporarily web-available)*
* hooks *(???)*
* www *(Web-available assets)*
	* local *(Local additions)*
		* (local directories, parallel to `../`)
	* css *(General/reusable css)*
	* js *(General/reusable js)*
	* modules *(Module-specific css/js/images)*
	* images, sized_images, sized_images_custom *(symlink to equivalent directories in `data/`)*
	* tmp *(symlink to `data/www_tmp/`)*
	* etc.
