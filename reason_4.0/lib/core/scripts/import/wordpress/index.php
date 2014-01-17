<?php
/**
 * Import Wordpress XML and use it to populate an existant empty Reason site.
 *
 * 1. Create a publication with blog posts and comments.
 * 2. Imports pages, including hierarchy, as the child of a page on the existing site.
 *
 * When reading the XML, we basically create a large stacked job set. Upon confirmation, we process the stacked set.
 *
 * If you wordpress XML file is larger than a megabyte or so, this script could require you to increase the memory
 * limits or allowed execution time. We do not attempt to gracefully handle failure at all. Use this script on a 
 * development server to test imports. For simple sites, it may work very well. For complex Wordpress sites that use
 * custom types, it likely will not.
 *
 * Right now there are really no options - you get what you get. This could be rearchitected to be a more proper
 * multistep Disco form, provide options for import, handle failure gracefully, etc. 
 *
 * It does not aim to do that right now. This code predates the job.php class in Reason (it used an early version of it) 
 * and it shows a bit.
 *
 * There are some wordpress features that are not supported in Reason - here is a list of items to note:
 *
 * - Reason supports post comments but not page comments - page comments will not be imported.
 * - Reason does not support threaded comments - comment hierarchies will be flattened.
 * - Reason does not support category parents - they are flattened.
 * - Reason does not support tags - they are converted to categories.
 *
 * Some things we do take care of that you might not think about:
 *
 * - Items of type "page" with SEO friendly old URLs are put into Reason's URL history so old page URLs will auto redirect.
 * - A post is placed directly into parent categories if it is not already a part of them.
 *
 * @todo filter out [caption id="attachment_901"] stuff in content
 * @todo handle attachments in some way?
 * @todo suggest server rewrites for pages that do not have friendly URLs
 * @todo XML parsing using XML Reader for better memory usage / performance
 * @todo blog_feed_string for publication should be customizable probably, along with lots of other publication fields.
 * @todo should we process tags as entity keywords instead of as categories? Or make it an option?
 * @todo refactor for multiphase architecture so we can process very large wordpress XML files
 *
 * @version alpha 1
 * @package reason
 * @subpackage scripts
 * @author Nathan White
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
include_once(DISCO_INC . 'controller.php');
reason_include_once('function_libraries/user_functions.php');

// Include the forms
reason_include_once('scripts/import/wordpress/SetupForm.php');
reason_include_once('scripts/import/wordpress/ConfirmForm.php');

$netid = reason_require_authentication(); // force login to a session
$reason_user_id = get_user_id( $netid );
if(empty($reason_user_id))
{
	die('valid Reason user required');
}
elseif(!reason_user_has_privs( $reason_user_id, 'upgrade' ) )
{
	die('You must have upgrade privileges to run this script');
}
else
{
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' ."\n";
	echo '<html xmlns="http://www.w3.org/1999/xhtml">' ."\n";
	echo '<head>' ."\n";
	echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
	echo '</head>' ."\n";
	echo '<body>' ."\n";
	
	if (isset($_GET['report']))
	{
		$link = carl_make_link(array('report' => ''));
		$cache = new ReasonObjectCache($_GET['report']);
		$report =& $cache->fetch();
		
		if (isset($report['report']))
		{
			echo '<h2>Full Report</h2>';
			echo $report['report'];
		}
		
		else echo '<p>Nothing to Report</p>';
		$link = carl_make_link(array('report' => ''));
		echo '<p><a href="'.$link.'">Do another Wordpress Import</a></p>';
	}
	else
	{
		// Initialize the controller and set a few options.
		$controller = new FormController;
		$controller->set_session_class('Session_PHP');
		$controller->set_session_name('REASON_SESSION');
		$controller->set_data_context('wordpress_import');
		$controller->show_back_button = false;
		$controller->clear_form_data_on_finish = true;
		$controller->allow_arbitrary_start = false;
		$controller->reason_user_id = $reason_user_id;
		
		// Set up the progression of forms.
		$forms = array(
			'SetupForm' => array(
				'start_step' => true,
				'next_steps' => array(
					'ConfirmForm' => array(
						'label' => 'Continue',
					),
				),
				'step_decision' => array(
					'type' => 'user',
				),
			),
			'ConfirmForm' => array(
				'final_step' => 'true',
				'final_button_text' => 'Confirm',
			),
		);
		
		$controller->add_forms( $forms );
		$controller->init();	
		$controller->run();
	}
	echo '</body>';
	echo '</html>';
}