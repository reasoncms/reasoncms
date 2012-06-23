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
	function get_cleanup_rules()
	{
		$cr = parent::get_cleanup_rules();
		$cr['show_all'] = array( 'function' => 'check_against_array', 
								 'extra_args' => array( 'true', 'false' ) );
								 
		$cr['policy_id'] = array( 'function' => 'turn_into_int' );
		return $cr;
	}
	
	protected function _get_es()
	{
		$es = parent::_get_es();
		$es->add_right_relationship( $this->page_id, relationship_id_of('page_to_policy') );
		return $es;
	}
	
	// Temporarily disabled until we can figure about a better way to support show_all
	/* function run() // {{{
	{
		if( !empty($this->request[ 'show_all' ]) )
		{
			// this->values contains the policies associated with this page.
			// lopo through those looking for root nodes to list
			foreach( $this->values AS $node )
				if( $node->id() == $node->get_value( 'parent_id' ) )
				{
					// HUUUUUUUUUUUUUUUUGE HACK
					// this really isn't that bad.  the reason it works is
					// because of the flawed design of the listers.  the policy
					// navigation lister depends on the policy_id IN the request
					// array to determine which root to use.  So what I'm doing
					// is just running the do_display method several times while
					// changing the internal storage of the policy_id
					$this->request['policy_id'] = $node->id();
					$this->pages->do_display();
				}

		}
		else
		{
			parent::run();
		}
	} // }}} */
}
	
?>
