<?php
/**
 * @package reason_local
 * @subpackage minisite_modules
 */

/**
 * Include the reason header, and register the module with Reason
 */
include_once( 'reason_header.php' );

/**
 * Include dependencies
 */
reason_include_once( 'minisite_templates/modules/profile/forms/tags_base.php' );

/**
 * Form for the personal tags section. Really this is here just as a placeholder in case we want to do specific customizations.
 */
class personalTagsProfileEditForm extends tagsBaseProfileEditForm
{

}