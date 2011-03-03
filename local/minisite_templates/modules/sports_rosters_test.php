<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	//reason_include_once( 'classes/error_handler.php');

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'sportsRosterTestModule';

	class sportsRosterTestModule extends DefaultMinisiteModule
	{
		var $clean_up_rules = array('player_id');

		function init( $args = array() )
		{
			trigger_error('test');
			$es = new entity_selector($this->site_id);
			$es->add_type(id_of('baseball_roster_type'));
			$es->add_right_relationship( $this->page_id, relationship_id_of('page_to_baseball_roster'));
			$result = $es->run_one();

			if ($result)
			{
				pray($result);
                        }
		}

		function has_content()
		{
			return true;
		}

		function run()
		{
			

			
		}
	}
?>
