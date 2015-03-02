<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
</head>
<link rel="stylesheet" type="text/css" href="./wizard.css" />
<script src="<?php echo JQUERY_URL; ?>" type="text/javascript"></script>
<script src="<?php echo REASON_HTTP_BASE_PATH; ?>js/jquery.arrayMaker.js" type="text/javascript"></script>
<script type="text/javascript">
	jQuery(document).ready(function()
		{

			options = {
				"class":		"_monkeyBusiness",
				"display_JSON":			"sometimes",
				"default_JSON":			"",
				"allow_multiple_types":	"true",
				"initially_visible":	"false",
				"visibility_toggle":	"true"
			};

			$("[name$='_params']").arrayMaker(options)
		}
	);
</script>

</head>
<body>
<?php
/**
 * A wizard for reason page type creation and modification
 *
 * This script provides a user interface for creating and editing page types.
 * It loads a selected page type or the default as a template for making changes.
 * After making changes, several export options are provided.
 * It is useful as a tool to enable someone with little or no knowledge of PHP to create a page type.
 *
 * The wizard makes use of the disco multi-step form controller, reason session control,
 * and the ReasonPageType and ReasonPageTypes classes. jQuery and the jQuery.arrayMaker.js file are required
 * for the parameter editing UI (otherwise users will have to edit raw JSON).
 *
 * @author Nathan White
 * @author Andrew Bacon
 * @package reason
 * @subpackage scripts
 */

/**
 * include dependencies
 */
include_once('reason_header.php');
reason_include_once('classes/page_types.php');
reason_include_once('minisite_templates/page_types.php');
reason_include_once('classes/session_php.php');
include_once(DISCO_INC . 'controller.php');

if (!carl_is_php5())
{
	echo '<p>Sorry this requires php 5 for now</p>';
	die;
}
// Require that whomever is using the form have access.
reason_require_authentication('','session');
// Include all of the forms.
reason_include_once('scripts/developer_tools/page_type_wizard/SelectForm.php');
reason_include_once('scripts/developer_tools/page_type_wizard/EditForm.php');
reason_include_once('scripts/developer_tools/page_type_wizard/FormatForm.php');

//Initialize the controller and set a few options.
$controller = new FormController;
$controller->set_session_class('Session_PHP');
$controller->set_session_name('REASON_SESSION');
$controller->set_data_context('page_type_wizard');
$controller->show_back_button = true;
$controller->clear_form_data_on_finish = false;
$controller->allow_arbitrary_start = false;

// Set up the progression of forms.
$forms = array(
	'SelectForm' => array(
		'start_step' => true,
		'next_steps' => array(
			'EditForm' => array(
				'label' => 'Select this page type',
			),
		),
		'step_decision' => array(
			'type' => 'user',
		),
	),
	'EditForm' => array(
		'next_steps' => array(
			'FormatForm' => array(
				'label' => 'Next',
			),
		),
		'step_decision' => array(
			'type' => 'user',
		),
	),
	'FormatForm' => array(
		'final_step' => array(
			'label' => 'export'
		),
	),
);

// Add, init, and run the forms.
$controller->add_forms( $forms );
$controller->init();
//$controller->set_request( $_REQUEST );
$controller->run();
?>
</body>
</html>
