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
		* popups *()*
		* scripts
		* ssh
		* theme_customizers
	* local *Area for [local customizations & enhancements](core_local.md)*
		* (local directories, parallel to `../core/`)
* data
	* assets
	* cache
	* csv_data
	* dbs
	* geocodes
	* images
	* logs
	* media
	* sized_images
	* sized_images_custom
	* tmp
	* www_tmp
* hooks
* www
	* local
		* (local directories, parallel to `../`)
	* css
	* js
	* modules
	* etc.
