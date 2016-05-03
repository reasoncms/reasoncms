<?php
/**
 * A disco class for Reason admin filter forms
 * @package reason
 * @subpackage classes
 */

	/**
	 * Required Includes
	 */
	include_once('reason_header.php');
	reason_include_once( 'classes/entity.php' );
	reason_include_once( 'classes/admin/admin_page.php' );
	include_once( DISCO_INC . 'disco.php' );

	/**
	 * This class works as a filter for the list_content page.  Prints out all the pre stuff
	 * then creates a form object.  Takes all global variables and puts them into hidden
	 * fields.
	 * @author Brendon Stanton
	 * @package reason
	 */
	class filter extends Disco
	{
	 	/**
	 	 * Default actions, there should only be one which is submit.
		 * @access private
		 * @var array
		 */
		var $actions = array( 'submit' => 'Search', 'clear' => 'Clear' );
	 	/**
		 * The admin page class passed in as a paramater.  This way, the form can access
		 * anything it needs from the class
		 * @access private
		 * @var AdminPage
		 */
		var $page;

		/**
		 * Sets page to bet the admin page.  Should get called before doing anything else
		 * @param AdminPage $page The admin page class
		 * @return void
		 */
		function set_page( &$page) // {{{
		{
			$this->page =& $page;
		} // }}}
		/**
		 * Grabs Any Search fields from the Viewer and sets up display names properly
		 * @param Viewer $viewer The viewer that we are a part of
		 * @return void
		 */
		function grab_fields( $viewer )
		{
			$this->set_form_method('get');
			if( !$this->has_filters() ) unset($this->actions['clear']); // remove filter if we have no search fields
			$this->add_element( 'search_exact_id' , 'hidden' );
			$this->set_value( 'search_exact_id' , true );
			$this->add_element( 'refresh_lister_state', 'hidden' );
			$this->set_value( 'refresh_lister_state', true );
			if( $viewer )
			{
				$this->get_db_fields( $viewer );
				reset( $viewer );
				while( list( $field , ) = each( $viewer ) )
				{
					$key = 'search_' . $field;
					
					//add fields in different ways
					if( !empty( $this->fields[ $field ] ) && preg_match( "/^enum\((.*)\)$/" , $this->fields[ $field ][ 'db_type' ] ) )
						$this->add_enum_element( $field );
					else
						$this->add_element( $key , 'text' , array( 'size' => 20 ) );
					
					if( isset( $this->page->request[ $key ] ) AND $this->page->request[ $key ] )
						$this->set_value( $key , $this->page->request[ $key ] );
					if( $key == 'search_datetime')
						$this->add_comments($key,form_comment('yyyy-mm-dd'));
					$this->set_display_name( $key , prettify_string($field) );
				}
			}
			foreach( $this->page->module->viewer->request as $key => $value )
			{
				if(!$this->is_element($key))
				{
					$this->add_element( $key, 'hidden');
					$this->set_value( $key, $value );
				}
			}
		}
		
		/**
	 	 * Checks the page to see if any search values have already been submitted
		 * @return bool
		 */
		function has_filters() // {{{
		{
			foreach( $_REQUEST AS $k => $r )
			{
				if( preg_match( '/^search_/' , $k ) && !$this->is_search_type( $k ) && !empty( $r ) )
					return true;
			}
			return false;
		} // }}}

		/**
	 	 * Returns true if the name of a variable is a search type
	 	 * @return bool
	 	 */
		function is_search_type( $name ) // {{{
		{
			if( preg_match( '/^search_exact_/' , $name ) ) return true;
			if( preg_match( '/^search_less_than_/' , $name ) ) return true;
			if( preg_match( '/^search_less_than_equal_/' , $name ) ) return true;
			if( preg_match( '/^search_greater_than_/' , $name ) ) return true;
			if( preg_match( '/^search_greater_than_equal_/' , $name ) ) return true;
			return false;
		} // }}}
		
		/**
		 * This adds a field as an enum element.  $field must be a field in reason
		 * @param string $field Name of the enum field
		 * @return void
		 */
		function add_enum_element( $field ) // {{{
		{
			$enum_string = $this->fields[ $field ][ 'db_type' ];

			preg_match( "/^enum\((.*)\)$/", $enum_string , $matches );
			 
			$options = array();
			$opts = array();
			$t = 'select';
			// explode on the commas
			$options = explode( ',', $matches[1] );
			// get rid of the single quotes at the beginning and end of the string
			// MySQL also escapes single quotes with single quotes, so if we see two single quotes, replace those two with one
			reset( $options );
			while( list( $key,$val ) = each ( $options ) )
				$options[ $key ] = str_replace("''","'",substr( $val,1,-1 ));
			reset( $options );
			while( list( ,$val ) = each( $options ) )
				$opts[ $val ] = $val;
			$args['options'] = $opts;
			$this->add_element( 'search_' . $field , 'select' , $args );
			$this->add_element( 'search_exact_' . $field , 'hidden' );
			$this->set_value( 'search_exact_' . $field , true );
		} // }}}
		/**
		 * Grabs a list of fields associated with the current type in case we need them later.
		 * Used in add_enum_element( $field ).
		 * @param Viewer $viewer
		 */
		function get_db_fields( $viewer ) // {{{
		{
			$n = count( $viewer );
			$i = 0;
			$in = '( ';
			foreach( $viewer AS $key => $t )
			{
				$i++;
				$in .= '"' . $key . '"';
				if( $i != $n )
					$in .= ', ';
			}
			$in .= ')';
			$d = new DBSelector;
			$d->add_table( 'entity' );
			$d->add_table( 'field' );
			$d->add_table( 'r1' , 'relationship' );
			$d->add_table( 'ar1' , 'allowable_relationship' );
			$d->add_table( 'r2' , 'relationship' );
			$d->add_table( 'ar2' , 'allowable_relationship' );
			
			$d->add_field( 'field' , '*' );
			$d->add_field( 'entity' , '*' );

			$d->add_relation( 'ar1.name = "type_to_table"' );
			$d->add_relation( 'ar2.name = "field_to_entity_table"' );

			$d->add_relation( 'r1.type = ar1.id' );
			$d->add_relation( 'r2.type = ar2.id' );
			$d->add_relation( 'entity.id = field.id' );

			$d->add_relation( 'r1.entity_a = ' . $this->page->type_id );
			$d->add_relation( 'r1.entity_b = r2.entity_b' );
			$d->add_relation( 'r2.entity_a = field.id' );
			if( $n > 0 )
				$d->add_relation( 'entity.name IN ' . $in );
			$fields = $d->run();
			$this->fields = array();
			foreach( $fields AS $field )
				$this->fields[ $field[ 'name' ] ] = $field;
			
		} // }}}
		/**
		 * after all error checking is finish, this finishes the search
		 * @return void
		 */
		function finish() // {{{
		{
			if( preg_match( '/clear/' , $this->get_chosen_action() ) )
			{
				$array = array('page' => false , 'submitted' => '', 'submit' => '', 'clear' => '',  'refresh_lister_state' => '1');
				if( !empty( $this->page->request[ 'order_by' ] ) )
					$array[ 'order_by' ] = $this->page->request[ 'order_by' ];
				if( !empty( $this->page->request[ 'dir' ] ) )
					$array[ 'dir' ] = $this->page->request[ 'dir' ];
				if( !empty( $this->page->request[ 'lister' ] ) )
					$array[ 'lister' ] = $this->page->request[ 'lister' ];
				if( !empty( $this->page->request[ 'state' ] ) )
					$array[ 'state' ] = $this->page->request[ 'state' ];

		    	$link =  unhtmlentities( $this->page->make_link( $array ) );
			}
			else
			{
		    	$link =  unhtmlentities( $this->page->make_link( array('page' => false , 'submitted' => '', 'submit' => '', 'clear' => '' ) , true ) );
			}
			return $link;
		} // }}}		
	}
?>