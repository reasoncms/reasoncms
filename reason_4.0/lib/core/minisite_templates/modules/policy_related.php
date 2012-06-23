<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
/**
 * Register module with Reason and include dependencies
 */
reason_include_once( 'minisite_templates/modules/policy.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'RelatedPolicyModule';

/**
 * A minisite module that displays the policies attached to the current page
 *
 * Note: Policies in this context refers to Reason entities of the type Policy, e.g. a nice way to manage the organization's
 * rules and regulations. This does not refer to any internal-to-reason rules enforced by machine code.
 */
class RelatedPolicyModule extends PolicyModule
{
	protected function _get_es()
	{
		$es = parent::_get_es();
		$es->add_right_relationship( $this->page_id, relationship_id_of('page_to_policy') );
		return $es;
	}
}
	
?>
