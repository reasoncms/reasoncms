<?php
/**
 * @package reason
 * @subpackage html_editors
 * @author Matt Ryan
 */
 
$GLOBALS[ '_reason_editor_integration_classes' ][ basename( __FILE__) ] = 'reasonEditorIntegrationBase';
/**
 * An interface definition for Editor integration classes
 *
 * Integration classes should extend this class and overload all methods
 *
 * Files must have the following to identify the class name:
 * $GLOBALS[ '_reason_editor_integration_classes' ][ basename( __FILE__) ] = 'nameOfClassGoesHere';
 */
class reasonEditorIntegrationBase
{
	/**
	 * Get the name of the plasmature element that should be used for this editor
	 * @return string name of the plasmature element
	 */
	function get_plasmature_type()
	{
		return 'textarea';
	}
	
	/**
	 * Get the appropriate parameters to pass to the plasmature element
	 * @param integer $site_id The Reason id of the site in which this editor is being invoked
	 * @param integer $user_id The Reason id of the current user (0 if user is anonymous or not in the Reason user store)
	 * @return array plasmature parameters 
	 */
	function get_plasmature_element_parameters($site_id, $user_id = 0)
	{
		return array();
	}
	
	/**
	 * Get the available configuration options for the editor
	 *
	 * These options are presented to administrators when setting up a Reason site
	 * Each option must be represented as a string <= 256 bytes, since it is stored in a tinytext field in the database
	 *
	 * @return array keys are values to be stored in the db and can then be used by @get_plasmature_element_parameters() when setting up the plasmature element, values are labels that are presented to the administrator
	 */
	function get_configuration_options()
	{
		return array();
	}
}
?>
