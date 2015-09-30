<?php
/**
 * Connection configuration for Thor form engine
 * @package thor
 */

/**
 * Use JavaScript based thor form editor
 * 
 * As of Reason 4.3 the JavaScript editor is in beta and this defaults to FALSE.
 * 
 * In future versions of Reason addition UI and testing work will be done and this setting will default to TRUE. 
 */
// define ('USE_JS_THOR', false);

/**
 * As of 2014-04-30, we have three versions of Thor represented by the constants below::
 * 1. the legacy flash implementation (THOR_VERSION_FLASH)
 * 2. "Formbuilder", the javascript editor that was in beta as of Reason 4.3 (THOR_VERSION_JS_OLD)
 * 3. the OTHER javascript editor that is also confusingly named Formbuilder: https://github.com/JSlote/formbuilder-rsn (THOR_VERSION_JS_FORMBUILDER)
 *
 * Each should be more or less a drop-in replacement for the other; they work differently under the hood, but the structure that
 * gets written out to the Reason "form" table should be compatible, if not completely identical. For now, the "USE_JS_THOR" constant
 * is insufficient and will be replaced with SOMETHING, so that we can easily switch between the three versions. However, once we're
 * confident that option 3 is going to work for us, it should be a priority to fully move to that one alone and rip this code out.
 */

define("THOR_VERSION_FLASH", "thor_version_flash");
define("THOR_VERSION_JS_OLD", "thor_version_js_old");
define("THOR_VERSION_JS_FORMBUILDER", "thor_version_js_formbuilder");

// define("USE_THOR_VERSION", THOR_VERSION_FLASH);
define("USE_THOR_VERSION", THOR_VERSION_JS_FORMBUILDER);

if (!defined('THOR_FORM_DB_CONN'))
{
	define ('THOR_FORM_DB_CONN', 'thor_connection');
}

// for forms that include upload components, where should the submitted files live?
define("THOR_SUBMITTED_FILE_STORAGE_BASEDIR", REASON_DATA_DIR . "thor_form_uploads/");
?>
