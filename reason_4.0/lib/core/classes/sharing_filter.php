<?php
	/**
	 * Required Includes
	 */
	reason_include_once( 'classes/filter.php' );

	/**
	 * New Filter class designed for sharing module.  Allows user to search by site.
	 * @author Brendon Stanton
	 * @package reason
	 */
	class sharing_filter extends filter
	{
		/**
		 * Overloaded Function From Filter
		 * 
		 * Calls the parent function and then adds the new site element to the form.
		 * @param Viewer $viewer the current viewer that's being used
		 * @return void
		 */
		function grab_fields( $viewer ) // {{{
		{
			parent::grab_fields( $viewer );
			$sites = $this->get_sites_with_available_associations();
			$this->add_element( 'search_site' , 'select', array( 'options' => $sites ) );
			$this->set_display_name( 'search_site' , 'site' );
			$this->add_element( 'search_exact_site' , 'hidden' );
			if( !empty( $this->page->request[ 'search_site' ] ) )
			{
				$this->set_value( 'search_site' , $this->page->request[ 'search_site' ] );
			}
		} // }}}
		/**
		 * Quick and dirty helper function that gets the sites with available associations with that type.
		 *
		 * This function just checks for all sites that are currently set to share objects of the given type.
		 * If the current site is live, it only selects those other sites which are also live.  It does
		 * not check the sites to see if the site is actually sharing anything.
		 * @return array An array of entities of the available sites in alphabetical order
		 */
		function get_sites_with_available_associations() //{{{
		{
			$cur_site = new entity ( $this->page->site_id );
			
			$es = new entity_selector();
			$es->add_type( id_of( 'site' ) );
			if($cur_site->get_value('site_state') == 'Live')
			{
				$es->add_relation('site.site_state = "Live"');
			}
			$es->add_relation('entity.id != '.$this->page->site_id);
			$es->add_left_relationship( $this->page->type_id , relationship_id_of( 'site_shares_type' ) );
			$es->set_order( 'name ASC' );
			$results = $es->run_one();
			$return_array = array();
			foreach($results AS $e)
			{
				$return_array[ $e->id() ] = $e->get_value( 'name' );
			}
			return $return_array;
		} // }}}
	}
?>
