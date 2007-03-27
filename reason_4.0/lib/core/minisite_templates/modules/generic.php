<?php

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'GenericModule';
	reason_include_once( 'minisite_templates/modules/default.php' );

	class GenericModule extends DefaultMinisiteModule
	{
		var $cleanup_rules = array(
			'item_id' => array('function' => 'turn_into_int')
		);
		function init( $args ) // {{{
		{
			$error = 'Your class needs to have a type id.  Please overload the set_type() function and '.
					 'include a line such as $this->type = id_of( "something" ) to run this module.';
			parent::init( $args );
			$this->set_type();
			if( empty( $this->type ) )
				trigger_error( $error , E_USER_ERROR );
			$this->es = new entity_selector( $this->parent->site_id );
			$this->es->add_type( $this->type );
			$this->alter_es();
			$this->items = $this->es->run_one();
			if( count( $this->items ) > 1 )
			{
				if( !empty( $this->request[ 'item_id' ] ) )
					foreach( $this->items AS $item )
						if( $item->id() == $this->request[ 'item_id' ] ) 
						{
							$this->parent->add_crumb( $item->get_value( 'name' ) );
							//$this->parent->title = $item->get_value( 'name' );
						}
			}
			else
			{
				reset( $this->items );
				$cur = current( $this->items );
				if( $cur )
				{
					//$this->parent->title = $cur->get_value( 'name' );
					$this->parent->add_crumb( $cur->get_value( 'name' ) );
				}
			}
		} // }}}
		function run() // {{{
		{
			if( count( $this->items ) > 1 )
			{
				if( !empty( $this->request[ 'item_id' ] ) )
					$this->_show_item( $this->request[ 'item_id' ] );
				else
					$this->list_items();

			}
			else
			{
				reset( $this->items );
				$cur = current( $this->items );
				if( $cur )
					$this->_show_item( $cur->id() );
			}
		} // }}}

		function _show_item( $id ) // {{{
		{
			foreach( $this->items AS $item )
			{
				if( $item->id() == $id )
				{
					$this->show_item_name( $item );
					$this->show_item_content( $item );
				}
			}
			if( count( $this->items ) > 1 )
				$this->show_back();
		} // }}}

		
		function list_items() // {{{
		{
			echo "<div class='genericList'>\n";
			foreach( $this->items AS $item )
			{
				$this->show_list_item( $item );
			}
			echo "</div>\n";
		} // }}}

		function set_type() // This must always be overloaded, or it will crash crash crash. {{{
		{
			//in here put something like:
			//$this->type = id_of( 'something' );
		} // }}}
		function alter_es() // {{{
		{
		} // }}}
		function show_list_item( $item ) // {{{
		{
			$link = '?item_id=' . $item->id();
			if (!empty($this->parent->textonly))
				$link .= '&amp;textonly=1';
			echo '<a class="genericListLink" href="' . $link . '">' . $item->get_value( 'name' ) . '</a><br />';
		} // }}}
		function show_item_name( $item ) // {{{
		{
			echo "<h3 class='genericName'>" . $item->get_value( 'name' ) . "</h3>\n";
		} // }}}
		function show_item_content( $item ) // {{{
		{
			echo "<div class='genericContent'>" . $item->get_value( 'content' ) . "</div>\n";
		} // }}}
		function show_back() // {{{
		{
			$link = '?';
			if (!empty($this->parent->textonly))
				$link .= 'textonly=1';
			echo '<div class="genericBackClass"><a href="'.$link.'">Back to List</a></div>';
		} // }}}
	}
?>
