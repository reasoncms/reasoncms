<?php
include_once( 'reason_header.php' );
reason_include_once( 'classes/mvc.php' );
reason_include_once( 'minisite_templates/modules/profile/lib/profile_functions.php' );

/**
 * Right now this just returns a profile connector class. It should instead BE that class possibly.
 */
 
$GLOBALS[ '_profiles_model' ][ basename( __FILE__, '.php' ) ] = 'DefaultProfileExploreModel';

class DefaultProfileExploreModel extends ReasonMVCModel
{
	function build()
	{
		$profile_config = profile_get_config();
		$connector_class = new $profile_config->connector_class;
		return $connector_class;
	}
}
?>