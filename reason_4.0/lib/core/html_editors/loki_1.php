<?php

/**
 * @package Reason
 * @author Matt Ryan
 */
 
reason_include('html_editors/base.php');

// Identify the class that should be used
$GLOBALS[ '_reason_editor_integration_classes' ][ basename( __FILE__) ] = 'reasonLoki1Integration';

/**
 * An editor integration class for Loki 1
 * 
 */
class reasonLoki1Integration extends reasonEditorIntegrationBase
{
	/**
	 * Get the name of the plasmature element that should be used for this editor
	 * @return string name of the plasmature element
	 */
	function get_plasmature_type()
	{
		return 'loki';
	}
	
	/**
	 * Get the appropriate parameters to pass to the plasmature element
	 * @param integer $site_id The Reason id of the site in which this editor is being invoked
	 * @param integer $user_id The Reason id of the current user (0 if user is anonymous or not in the Reason user store)
	 * @return array plasmature parameters 
	 */
	function get_plasmature_element_parameters($site_id, $user_id = 0)
	{
		
		$params = array();
		
		// site id
		$params['site_id'] = $site_id;
		
		// default widgets
		$site = new entity($site_id);
		if($site->get_value( 'loki_default' ))
		{
			$params['widgets'] = $site->get_value( 'loki_default' );
		}
		if( !empty($user_id) && reason_user_has_privs( $user_id, 'edit_html', $site_id ) )
		{
			$params['user_is_admin'] = true;
		}
		else
		{
			$params['user_is_admin'] = false;
		}
		return $params;
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
		include_once(LOKI_INC.'lokiOptions.php3');
		
		$options_object = new Loki_Options();
		$options = $options_object->get_all();
		foreach ( $options as $k => $v )
			$options[$k] = prettify_string($k);
		return $options;
	}
}

?>