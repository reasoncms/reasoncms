<?php
/**
 * Wordpress import script - a multistep Disco based form.
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
include_once(DISCO_INC . 'controller.php');
reason_include_once('classes/session_php.php');

ini_set('display_errors', 'on');

/**
 * Wordpress import script
 *
 * Imports a wordpress site into an existing reason site. Handles the following:
 *
 * 1. Create a publication with blog posts and comments.
 * 2. Imports pages, including hierarchy, as the child of a page on the existing site.
 *
 * Currently out of scope:
 *
 * 1. Wordpress supports parent categories ... Reason does not ... right now we ignore parent categories.
 *
 * @version alpha 1
 * @author Nathan White
 */

ini_set("memory_limit", "256M");
set_time_limit(120);

// Include the forms
reason_include_once('scripts/import/wordpress/SetupForm.php');
reason_include_once('scripts/import/wordpress/SetupForm2.php');
reason_include_once('scripts/import/wordpress/ReviewForm.php');

$netid = reason_require_authentication('','session'); // force login to a session
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
	echo '<script src="'. JQUERY_URL. '" type="text/javascript"></script>' ."\n";
	echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
	echo '</head>' ."\n";
	echo '<body>' ."\n";
	
	if (isset($_GET['report']))
	{
		$link = carl_make_link(array('report' => ''));
		echo '<p><a href="'.$link.'">Do another Wordpress Import</a></p>';
		$cache = new ReasonObjectCache($_GET['report']);
		$report =& $cache->fetch();
		
		if (isset($report['alerts']))
		{
			echo '<h2>The following alerts likely require attention right away in order for your site to work properly.</h2>';
			foreach ($report['alerts'] as $k=>$v)
			{
				echo '<h3>' . $k . '</h3>';
				echo $v;
			}
		}
		if (isset($report['report']))
		{
			echo '<h2>The full report is mostly useful for developers.</h2>';
			pray ($report['report']);
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
		$controller->show_back_button = true;
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
				
				//'next_steps' => array(
				//	'ReviewForm' => array(
				//		'label' => 'Continue',
				//	),
				//),
				//'step_decision' => array(
				//	'type' => 'user',
				//),
			)
			//,
			//'ReviewForm' => array(
			//	'final_step' => 'true',
			//	'final_button_text' => 'Confirm',
			//),
		);
		
		$controller->add_forms( $forms );
		$controller->init();	
		$controller->run();
	}
	echo '</body>';
	echo '</html>';
}
?>
