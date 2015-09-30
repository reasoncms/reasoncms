<?php
/**
 * @package reason
 * @subpackage content_sorters
 */
	/**
	 * Register sorter with Reason
	 */
	$sorter = 'registration_slot_sorter';
        $GLOBALS[ '_content_sorter_class_names' ][ basename( __FILE__) ] = 'registration_slot_sorter';

	/**
	 * A content sorter for registration slots
	 */
	class registration_slot_sorter extends sorter
	{
		//sort by:
			//event
			//then by default sort?
	
		function init() // {{{
		{
			parent::init();
			if( !empty( $this->admin_page->request[ 'event_id' ] ) )
			{
				$event = new entity($this->admin_page->request[ 'event_id']);
				$this->admin_page->title = 'Sorting Registration Slots of "' . $event->get_value('name') . '"';
			}
		} // }}}
		function update_es( $es ) // {{{
		{
			if( !empty( $this->admin_page->request[ 'event_id' ] ) )
			{
				$es->add_right_relationship_field( 'event_type_to_registration_slot_type' , 'entity' , 'id' , 'event_id' );
				$es->add_right_relationship_field( 'event_type_to_registration_slot_type' , 'entity' , 'name' , 'event_name' );
				if( !empty( $this->admin_page->request[ 'event_id' ] ) )
					$es->add_relation( '__entity__.id = ' . $this->admin_page->request[ 'event_id' ] ); 
			}
			return $es;
		} // }}}
		
		function get_links() // {{{
		{
			$links = parent::get_links();
			$es = new entity_selector( $this->admin_page->site_id );
			$es->add_type( id_of( 'event_type' ) );
			$es->set_order( 'dated.datetime DESC' ); 
			$values = $es->run_one();
			
			//should adjust so that can't rearrange slots for events that have only one or no registration slots.
			//also, probably not for past events either.
			if ($values)
			{
				foreach( $values AS $event_id => $event )
				{
					$es2 = new entity_selector( $this->admin_page->site_id );
					$es2->add_type( id_of( 'registration_slot_type' ) );
					$es2->add_right_relationship($event_id, relationship_id_of('event_type_to_registration_slot_type'));
					$numSlots = $es2->get_one_count();
				
					if($numSlots > 1)
					{
						$date = $event->get_value('datetime');
						$name = 'Sort slots for ' . $event->get_value('name') . ' - ' . prettify_mysql_datetime($date);
						$link = $this->admin_page->make_link( array( 'event_id' => $event->id() , 'default_sort' => false ) , true );
						$links[ $name ] = $link;
					}
				}
				$this->links = $links;
				return( $this->links );
			}
		} // }}}
	}
?>
