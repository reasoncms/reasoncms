<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
 	/**
 	 * Include parent class & register module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'GalleryModule';
	
	/**
	 * A module that displays images attached to the page in a grid-style gallery
	 *
	 * This module is deprecated; use gallery2 instead.
	 *
	 * @deprecated
	 * @todo Move out of the core
	 */
	class GalleryModule extends DefaultMinisiteModule
	{
		var $rows = 4;
		var $columns = 3;
		var $page_class = "smallText";
		var $cleanup_rules = array(
			'search_image' => array('function' => 'turn_into_string'),
			'search_date' => array('function' => 'turn_into_date'),
			'page' => array('function' => 'turn_into_int')
		);
		function init( $args = array() ) // {{{
		{
			parent::init( $args );
			$es = new entity_selector();
			$es->description = 'Selecting images for the gallery';
			$es->add_type( id_of('image') );
			$es = $this->refine_es( $es );
			if( !empty( $this->request[ 'search_image' ] ) )
			{
				$es->add_relation( '(entity.name LIKE "%'.reason_sql_string_escape($this->request[ 'search_image' ]).
								   '%" OR meta.description LIKE "%' . reason_sql_string_escape($this->request[ 'search_image' ]) . '%"'.
								   ' OR meta.keywords LIKE "%' . reason_sql_string_escape($this->request[ 'search_image' ]) . '%"'.
								   ' OR chunk.content LIKE "%' . reason_sql_string_escape($this->request[ 'search_image' ]) . '%"'.
								   ')' );
			}
			$this->num = $es->get_one_count();
			$this->check_bounds();
			$es->set_num( $this->num_per_page );
			$es->set_start( ( $this->request[ 'page' ] - 1 )* $this->num_per_page );

			$this->images = $es->run_one();
		} // }}}
		function refine_es( $es )
		{
			$es->add_right_relationship( $this->page_id, relationship_id_of('minisite_page_to_image') );
			$es->add_rel_sort_field( $this->page_id, relationship_id_of('minisite_page_to_image'), 'rel_sort_order');
			
			// order first by rel_sort_order if that is not defined second criteria is dated.datetime ASC - this keeps pages that change to gallery pages reasonably predictable
			$es->set_order( 'rel_sort_order ASC, dated.datetime ASC' ); 
			return $es;
		}
		function show_search_function() // {{{
		{
			$value = isset( $this->request[ 'search_image' ] ) ? $this->request[ 'search_image' ] : '';
			?>
				<form action="">
					<strong>Search Images</strong>&nbsp;&nbsp;<input type="text" name="search_image" value="<?php echo $value;?>"/>
					<input type="hidden" name="page" value="1"/>&nbsp;&nbsp;
					<?php if ( !empty( $this->textonly ) )
							echo '<input type="hidden" name="textonly" value="1"/>';
					?>
					<input type="submit" value="Search!"/>
					<?php
						if( !empty($value) )
							echo '<span class="smallText"><a href="?textonly='.$this->textonly.'">Clear Search</a></span>';
					?>
				</form>
			<?php
		} // }}}
		function check_bounds() // {{{
		{
			$this->num_per_page = $this->rows * $this->columns;
			$this->num_pages = ceil( $this->num / $this->num_per_page );
			if( empty( $this->request[ 'page' ] ) )
			{
				$this->request[ 'page' ] = 1;
			}
			
			if( $this->request[ 'page' ] > $this->num_pages )
				$this->request[ 'page' ] = $this->num_pages;
			if( $this->request[ 'page' ] < 1 )
				$this->request[ 'page' ] = 1;
		} // }}}
		function has_content() // {{{
		{
			if( $this->images || isset( $this->request[ 'search_image' ] ))
				return true;
			else
				return false;
		} // }}}
		function run() // {{{
		{
			$col = 0;
			
			if( empty( $this->images ) && !empty( $this->request[ 'search_image' ] ) )
			{
				echo 'No images found.';
				$this->show_search_function();
			}
			elseif( !empty($this->textonly) )
			{
				echo '<h3>Images</h3>'."\n";
				foreach( $this->images AS $id => $image )
				{
					echo '<div class="imageChunk">';
					show_image( $image, false,true,true,false,true );
					echo "</div>";
				}
				$this->show_search_function();
				$this->show_paging();
			}
			else
			{
				echo '<table>';
				foreach( $this->images AS $id => $image )
				{
					if( $col == 0 )
						echo "<tr>";
					echo "<td align='left' valign='bottom'>";
					echo "<div class=\"imageChunk\">";
					show_image( $image, false,true,true,false,false,false );
					echo "</div></td>\n";
					$col = ( $col + 1 ) % $this->columns;
					if( $col == 0 )
						echo "</tr>";
				}
				while( $col != 0 )
				{
					$col = ( $col + 1 ) % $this->columns;
					echo '<td>&nbsp;</td>';
				}
				echo '</tr></table>';
				$this->show_search_function();
				$this->show_paging();
			}
		} // }}}
		function show_paging() // {{{
		{
			if( $this->num_pages > 1 )
			{
				echo '<div class="' . $this->page_class . '">';
				echo '<strong>Page: </strong>';
				for( $i=1; $i<=$this->num_pages; $i++ )
				{
					if( $i == $this->request[ 'page' ] )
						echo '<strong>' . $i . '</strong> ';
					else
					{
						echo '<a href="?page=' . $i;
						if( !empty( $this->request[ 'search_date' ] ) )
							echo '&amp;search_date=' . $this->request[ 'search_date' ];
						if( !empty( $this->request[ 'search_image' ] ) )
							echo '&amp;search_image=' . $this->request[ 'search_image' ];
						if ( !empty( $this->textonly ) )
							echo '&amp;textonly=1';
						
						echo '">' . $i . '</a> ';
					}
				}
				echo '</div>';
			}
		} // }}}
	}
?>
