<?php
/**
 * @package reason
 * @subpackage classes
 */
 
/**
 * Include dependencies
 */
include_once( 'reason_header.php' );
include_once( CARL_UTIL_INC . 'basic/misc.php' );
reason_include_once( 'classes/mvc.php' );
reason_include_once( 'classes/object_cache.php' );
reason_include_once( 'classes/social.php' );

/**
 * Register MVC component with Reason
 */
$GLOBALS[ '_reason_mvc_model_class_names' ][ reason_basename( __FILE__) ] = 'ReasonSocialProfileLinksModel';
	
/**
 * ReasonSocialProfileLinksModel returns structured data containing social profile links for those social accounts
 * that support the SocialAccountProfileLinks interface defined in classes/social.php.
 *
 * The data returned is a PHP array indexed by the id of the social account entity, with the following components:
 *
 * - icon_small
 * - icon_large
 * - text
 * - src
 *
 * User Configurables
 *
 * - site_id
 *
 * @todo should we cache?
 * @author Nathan White
 */
class ReasonSocialProfileLinksModel extends ReasonMVCModel
{		
	/**
	 * Make sure that the model is configured with a valid URL.
	 *
	 * @return string json
	 */
	function build()
	{
		if ($site_id = $this->config('site_id'))
		{
			$s = get_microtime();
			$es = new entity_selector();
			$es->add_type(id_of('social_account_type'));
			$es->add_right_relationship($site_id, relationship_id_of('site_to_social_account'));
			$es->limit_tables();
			$es->limit_fields();
			if ($results = $es->run_one())
			{
				$result_keys = array_keys($results);
				$sih = reason_get_social_integration_helper();
				foreach ($result_keys as $id)
				{
					// get the integrator if it supports the SocialAccountProfileLinks interface
					if ($integrator = $sih->get_social_account_integrator($id, 'SocialAccountProfileLinks'))
					{
						$profile_links[$id]['icon'] = $integrator->get_profile_link_icon($id);
						$profile_links[$id]['text'] = $integrator->get_profile_link_text($id);
						$profile_links[$id]['href'] = $integrator->get_profile_link_href($id);
					}
				}
				if (!empty($profile_links)) return $profile_links;
			}
			return false;
		}
		else
		{
			trigger_error('The ReasonSocialProfileLinksModel must be provided with the configuration parameter site_id.', FATAL);
		}
	}
}
?>