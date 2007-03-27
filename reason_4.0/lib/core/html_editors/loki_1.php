<?php

$GLOBALS[ '_html_editor_plasmature_types' ][ basename( __FILE__) ] = 'loki';
$GLOBALS[ '_html_editor_param_generator_functions' ][ basename( __FILE__) ] = 'get_loki_params';
$GLOBALS[ '_html_editor_options_function' ][ basename( __FILE__) ] = 'get_loki_options';

	function get_loki_params($site_id, $user_id = 0)
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
		
		// user is admin
		if( !empty($user_id) && (user_is_a($user_id, id_of('admin_role'))
					|| user_is_a($user_id, id_of('power_user_role') ) ) )
		{
			$params['user_is_admin'] = true;
		}
		else
		{
			$params['user_is_admin'] = false;
		}
		return $params;
		
	}
	function get_loki_options()
	{
		include_once(LOKI_INC.'lokiOptions.php3');
		
		$options_object = new Loki_Options();
		$options = $options_object->get_all();
		foreach ( $options as $k => $v )
			$options[$k] = prettify_string($k);
		return $options;
	}
?>