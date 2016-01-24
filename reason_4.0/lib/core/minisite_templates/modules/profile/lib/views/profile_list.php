<?php
include_once( 'reason_header.php' );
reason_include_once( 'classes/mvc.php' );

$GLOBALS[ '_profiles_view' ][ basename( __FILE__, '.php' ) ] = 'DefaultProfileListView';

class DefaultProfileListView extends ReasonMVCView
{
	var $str;
	
	function get()
	{
		$people = $this->data();
		$config = profile_get_config();
		if (!empty($people))
		{
			$str = '';
			$str .= '<h2>Profile List</h2>';
			$str .= '<ul>';
			foreach ($people as $id => $person)
			{
				$str .= '<li><a href="'.profile_construct_link(array('username' => $person->get_username())).'">' . $person->get_display_name() . '</a></li>'."\n";
			}
		$str .= '</ul>';
		}
		else
		{
			$str .= '<p>No profiles available yet!</p>';
		}
		return $str;
	}
}
?>