<?php
	reason_include_once('classes/admin/modules/default.php');
	class sorter
	{
		var $admin_page;
		function sorter( &$page ) // {{{
		{
			$this->admin_page = &$page;
		} // }}}
		function init() // {{{
		{
			$es = $this->get_entity_selector();
			$this->values = $es->run_one();

			if( $this->is_new() )
			{
				$this->get_links();
				if( count( $this->links ) == 1 )
				{
					$l = unhtmlentities( current( $this->links ) );
					header( 'Location: ' . $l );
					die();
				}
			}
		} // }}}
		function update_es( $es ) // {{{
		{
			return $es;
		} // }}}
		function show_extras() // {{{
		{
			echo '&nbsp;';
		} // }}}
		function get_entity_selector() // {{{
		{
			$es = new entity_selector( $this->admin_page->site_id );
			$es->add_type( $this->admin_page->type_id );
			$es->set_order( 'sortable.sort_order ASC' );
			$es->set_sharing( 'owns' );
			$es = $this->update_es( $es );
			return $es;
		} // }}}
		function show_menu() // {{{
		{
			$num = count( $this->values );
			if( $num )
			{
				$size = ( $num > 25 ? 25 : $num + 1 );
				echo '<select multiple size='.$size.' name="list2">';
				foreach( $this->values AS $v )
				{
					echo '<option value="'.$v->id().'">'.strip_tags( $v->get_display_name() )."</option>\n";
				}
				echo '</select>';
			}
		} // }}}
		function get_links() // {{{
		{
			$link = $this->admin_page->make_link( array( 'default_sort' => false ) , true );
			$this->links = array( 'Sort All Items' => $link );
			return $this->links;
		} // }}}
		function is_new() // {{{
		{
			if( empty( $this->admin_page->request[ 'default_sort' ] ) )
				return false;
			else
				return true;
		} // }}}
		function show_links() // {{{
		{
			foreach( $this->links AS $name => $link )
				echo '<a href="'.$link.'">'.$name."</a><br />\n";
		} // }}}
	}

	class SortingModule extends defaultModule
	{

		var $type_entity;
		var $sorter;

		function init() // {{{
		{
			$type_entity = new entity( $this->admin_page->type_id );
			$this->type_entity = $type_entity;
			$this->admin_page->title = 'Sorting ' . prettify_string( $type_entity->get_value( 'plural_name' ) );
			
			if( $this->type_entity->get_value( 'custom_sorter' ) )
			{
				reason_include_once( 'content_sorters/' . $this->type_entity->get_value( 'custom_sorter' ) );
				$sorter = $GLOBALS[ '_content_sorter_class_names' ][ $this->type_entity->get_value( 'custom_sorter' ) ];
				$this->sorter = new $sorter( $this->admin_page );
			}
			else
				$this->sorter = new sorter( $this->admin_page );
			$this->sorter->init();
		} // }}}
		function run() // {{{
		{
			$fields = get_fields_by_type( $this->admin_page->type_id );
			if( is_array($fields) && in_array( 'sort_order' , $fields ) )
			{
				if( empty( $this->admin_page->request[ 'order' ] ) )
				{
					if( $this->sorter->is_new() )
					{
						$this->sorter->show_links();
					}
					else
					{
						$this->show_top_code();
						$this->show_bottom_code();
					}
				}
				else
					$this->set_order();
			}
			else
				echo 'This type is not sortable.';
		} // }}}
		function show_top_code() // {{{
		{
		?>
		<SCRIPT LANGUAGE="JavaScript">
        <!-- Begin
            function Moveup(dbox) 
            {
                for(var i = 0; i < dbox.options.length; i++) 
                {
                    if (dbox.options[i].selected && dbox.options[i] != "" && dbox.options[i] != dbox.options[0]) 
                    {
                        var tmpval = dbox.options[i].value;
                        var tmpval2 = dbox.options[i].text;
                        dbox.options[i].value = dbox.options[i - 1].value;
                        dbox.options[i].text = dbox.options[i - 1].text
                        dbox.options[i-1].value = tmpval;
                        dbox.options[i-1].text = tmpval2;
						
						dbox.options[i-1].selected=true;
						dbox.options[i].selected=false;

						i = dbox.options.length;
                    }
                }
            }
            function Movedown(ebox) 
            {
                for(var i = 0; i < ebox.options.length; i++) 
                {
                    if (ebox.options[i].selected && ebox.options[i] != "" && ebox.options[i+1] != ebox.options[ebox.options.length]) 
                    {
                        var tmpval = ebox.options[i].value;
                        var tmpval2 = ebox.options[i].text;
                        ebox.options[i].value = ebox.options[i+1].value;
                        ebox.options[i].text = ebox.options[i+1].text
                        ebox.options[i+1].value = tmpval;
                        ebox.options[i+1].text = tmpval2;
						
						ebox.options[i+1].selected=true;
						ebox.options[i].selected=false;
						
						i = ebox.options.length;
                    }
                }
            }
			function cycle(ebox) {
   			 var answer = '';
    			for (var i = 0; i<ebox.options.length; i++) {
    
                	answer += ebox.options[i].value + ' ';

    			}
			document.sortForm.order.value = answer;
			}
			//  End -->

            </script>

		<?php
		} // }}}
		function show_bottom_code() // {{{
		{
		?>
			<h5>To change the order of the list, simply highlight an item, then move it up and down using the arrows.</h5>
  			<form ACTION="" METHOD="POST" name="sortForm" action="<?php echo $_SERVER[ 'PHP_SELF' ]; ?>">
            <table>
            <tr>
            <td>
            <a href="#" onclick="javascript:Moveup(document.sortForm.list2);"><img src="<?php echo REASON_ADMIN_IMAGES_DIRECTORY; ?>arrow_up.gif" border="0"></a><br />
            <a href="#" onclick="javascript:Movedown(document.sortForm.list2);"><img src="<?php echo REASON_ADMIN_IMAGES_DIRECTORY; ?>arrow_down.gif" border="0"></a><br />
            </td>
            <td>
			<?php
				$this->sorter->show_menu();		
           	?>
		   	</td>
			<td>
			<?php
				$this->sorter->show_extras();
			?>
			</td>
            </tr>
            </table>
			<input type="hidden" name="order" value="">
			<?php
				foreach( $this->admin_page->request AS $k => $v )
					echo '<input type="hidden" name="'.$k.'" value="'.$v.'">' . "\n";
			?>
			<br /><br /><input type="submit" name="submit" value="Save Order" onclick="cycle(this.form.list2);">
            </form>
		<?php
		} // }}}
		function set_order() // {{{
		{
			$ids = explode( ' ' ,  $this->admin_page->request[ 'order' ] );
			$i = 1;
			foreach( $ids AS $id )
			{
				if( $id )
				{
					$q = 'UPDATE sortable set sort_order = ' . $i . ' WHERE id = ' . $id;
					db_query( $q , 'Error setting sort_order IN SortingModule::set_order()' );
					$i++;
				}
			}
			header( 'Location: ' . unhtmlentities( $_SESSION[ 'listers' ][ $this->admin_page->site_id ][ $this->admin_page->type_id ] ) ); 
			die();
		} // }}}
	}
?>
