<?php
include_once( 'reason_header.php' );
reason_include_once( 'classes/mvc.php' );
reason_include_once( 'minisite_templates/modules/profile/lib/profile_functions.php' );

//@todo does not have access to config with person_class yet.

$GLOBALS[ '_profiles_model' ][ basename( __FILE__, '.php' ) ] = 'DefaultProfileListModel';

class DefaultProfileListModel extends ReasonMVCModel
{
	function build()
	{
		$profiles = profile_get_site_profile_entities($this->config('site_id'));
		if (!empty($profiles))
		{
			$profile_config = profile_get_config();
			foreach ($profiles as $id => $entity)
			{
				$persons[$id] = new $profile_config->person_class($entity->get_value('user_guid'), 'ds_guid');
			}
			return $persons;
		}
		else
		{
			return '';
		}
	}
}
?>