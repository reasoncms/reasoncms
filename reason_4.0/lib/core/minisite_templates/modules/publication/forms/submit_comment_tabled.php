<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

/**
 * Include Disco
 */
reason_include_once( 'minisite_templates/modules/publication/forms/submit_comment.php' );

/**
 * Register the form with Reason
 */
$GLOBALS[ '_publication_comment_forms' ][ basename( __FILE__, '.php' ) ] = 'commentFormTabled';

/**
 * Comment submission form
 */
class commentFormTabled extends Disco
{
	var $box_class = 'Box';
}
?>
