<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	reason_include_once( 'classes/google_mapper.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'GoogleMapModule';
	
	class GoogleMapModule extends DefaultMinisiteModule
	{
		function init( $args = array() )
		{

		}
		function has_content()
		{
			return true;
		}
		function run()
		{
			$site_id = $this->site_id;
			$es = new entity_selector( $site_id );
			$es->add_type( id_of( 'google_map_type' ) );
			$es->add_right_relationship($this->cur_page->id(), relationship_id_of('page_to_google_map'));
			$es->add_rel_sort_field($this->cur_page->id(), relationship_id_of('page_to_google_map'));
			$es->set_order('rel_sort_order'); 
			$gmaps = $es->run_one();

			draw_google_map($gmaps);
		}
	}
?>
