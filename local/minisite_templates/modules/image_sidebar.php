<?php
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ImageSidebarModule';
	
	class ImageSidebarModule extends DefaultMinisiteModule
	{
		var $es;
		var $images;

		var $acceptable_params = array(
		'num_to_display' => '',
		'caption_flag' => true,
		'rand_flag' => false,
		'order_by' => '' );

		function init( $args = array() ) // {{{
		{
			parent::init( $args );

			$head_items =& $this->parent->head_items;
                        //$head_items->add_javascript(JQUERY_URL);
			$head_items->add_javascript( '/javascripts/highslide/highslide-full.js' );

			$this->es = new entity_selector();
			$this->es->description = 'Selecting images for sidebar';
			$this->es->add_type( id_of('image') );
			$this->es->set_env( 'site' , $this->site_id );
			$this->es->add_right_relationship( $this->parent->cur_page->id(), relationship_id_of('minisite_page_to_image') );
			if ($this->params['rand_flag']) $this->es->set_order('rand()');
			elseif (!empty($this->params['order_by'])) $this->es->set_order($this->params['order_by']);
			else
			{
				//echo $this->parent->cur_page->id();
				//die;
				$this->es->add_rel_sort_field( $this->parent->cur_page->id(), relationship_id_of('minisite_page_to_image') );
				$this->es->set_order('rel_sort_order');
			}
			if (!empty($this->params['num_to_display'])) $this->es->set_num($this->params['num_to_display']);
			$this->images = $this->es->run_one();
		} // }}}
		function has_content() // {{{
		{
			if( $this->images )
				return true;
			else
				return false;
		} // }}}
		function run() // {{{
		{
			$die = isset( $this->die_without_thumbmail ) ? $this->die_without_thumbnail : false;
			$popup = isset( $this->show_popup_link ) ? $this->show_popup_link : true;
			$desc = isset( $this->description ) ? $this->description : true;
			$text = isset( $this->additional_text ) ? $this->additional_text : "";
			
			if ( !empty($this->parent->textonly) )
				echo '<h3>Images</h3>'."\n";
			
			foreach( $this->images AS $id => $image )
			{
				$show_text = $text;
				if( !empty( $this->show_size ) )
					$show_text .= '<br />('.$image->get_value( 'size' ).' kb)';
				echo "<div class=\"imageChunk\">";
				if ($this->params['caption_flag']) show_image( $image, $die, $popup, $desc, $show_text, $this->parent->textonly,false );
				elseif ($this->params['caption_flag'] == false) show_image( $image, $die, $popup, false, $show_text, $this->parent->textonly,false );
				echo "</div>\n";
			}
		} // }}}
		function last_modified() // {{{
		{
			if( $this->has_content() )
			{
				$temp = $this->es->get_max( 'last_modified' );
				return $temp->get_value( 'last_modified' );
			}
			else
				return false;
		} // }}}
		
		function get_documentation()
		{
			if(!empty($this->params['num_to_display']))
				$num = $this->params['num_to_display'];
			else
				$num = 'all';
			if($num == 1)
				$plural = '';
			else
				$plural = 's';
			if($this->params['caption_flag'])
				$caption_text = 'without caption';
			else
				$caption_text = 'with caption';
			$ret = '<p>Displays '.$num.' image'.$plural.', '.$caption_text.$plural;
			if($this->params['order_by'])
				$ret .= ', using this order: '.$this->params['order_by'];
			if($this->params['rand_flag'])
				$ret .= ' (chosen at random)';
			$ret .= '</p>';
			return $ret;
		}
	}
?>
