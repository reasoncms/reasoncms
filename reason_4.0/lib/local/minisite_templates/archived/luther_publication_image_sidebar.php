<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 	/**
 	 * Include parent class and register module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/publication/module.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherPublicationImageSidebarModule';
	
	/**
	 * A Luther customized minisite module that displays publication images in the sidebar
	 * instead of in the publication itself. Used by the luther2010 theme.
	 */
	
	class LutherPublicationImageSidebarModule extends PublicationModule
	{
		var $es;
		var $images;

		var $acceptable_params = array(
		'num_to_display' => '',
		'caption_flag' => true,
		'rand_flag' => false,
		'order_by' => '' ,
		);

		function init( $args = array() )
		{
			$theme = get_theme($this->site_id);			
			if ($theme->get_value( 'name' ) != 'luther2010')
			{
				return;
			}
			parent::init( $args );
			//print_r($this->request);
			$item_id = !empty($this->request['story_id']) ? $this->request['story_id'] : NULL;
			if ($item_id == NULL)
				return;
			
			$es = new entity_selector();
			$es->set_env( 'site' , $this->site_id );
			$es->description = 'Selecting images for news item';
			$es->add_type( id_of('image') );
			$es->add_right_relationship( $item_id, relationship_id_of('news_to_image') );
			$es->add_rel_sort_field( $item_id, relationship_id_of('news_to_image') );
			$es->set_order('rel_sort_order');
			$this->images = $es->run_one();
		}
		function has_content()
		{
			if( $this->images )
				return true;
			else
				return false;
		}
		function run()
		{
			$die = isset( $this->die_without_thumbmail ) ? $this->die_without_thumbnail : false;
			$popup = isset( $this->show_popup_link ) ? $this->show_popup_link : true;
			$desc = isset( $this->description ) ? $this->description : true;
			$text = isset( $this->additional_text ) ? $this->additional_text : "";
			
			if ( !empty($this->textonly) )
				echo '<h3>Images</h3>'."\n";
			
			foreach( $this->images AS $id => $image )
			{
				$this->get_images_section($id, $image);
				/*$url = WEB_PHOTOSTOCK . $id . '.' . $image->get_value('image_type');
				
				echo '<div class="figure">';
				echo '<img src="' . $url . '" />';
				// show caption if flag is true
				if ($this->params['caption_flag'] && $caption != "")
				{
					echo $image->get_value('description') ;
				}
				echo "</div>   <!-- class=\"figure\" -->\n";*/
			}
		}
		function last_modified()
		{
			if( $this->has_content() )
			{
				$temp = $this->es->get_max( 'last_modified' );
				return $temp->get_value( 'last_modified' );
			}
			else
				return false;
		}
		
		function get_images_section($id, $image)
		{
			$markup_string = '';

			$imgtype = $image->get_value('image_type');
			$full_image_name = WEB_PHOTOSTOCK.$id.'.'.$imgtype;
			
			if ($this->cur_page->get_value( 'custom_page' ) != 'spotlight_archive')
			{			
				$url = WEB_PHOTOSTOCK . $id . '.' . $image->get_value('image_type');
				$thumb = WEB_PHOTOSTOCK . $id . '_tn.' . $image->get_value('image_type');
				$orig = WEB_PHOTOSTOCK . $id . '_orig.' . $image->get_value('image_type');
				$d = max($image->get_value('width'), $image->get_value('height')) / 125.0;
				if (preg_match("/hide_caption/", $image->get_value('keywords')))
				{
					$caption = "";
				}
				elseif (preg_match("/[A-Za-z0-9]+/", $image->get_value('content')))
				{
					$caption = $image->get_value('content');
				}
				else
				{
					$caption = $image->get_value('description');
				}
				ob_start();
				echo '<div class="figure" style="width:' . intval($image->get_value('width')/$d) .'px;">';
				// show href to full size image with class and onclick for highslide
				echo '<a href="'. $url . '" class="highslide" onclick="return hs.expand(this, imageOptions)">';
				//echo '<img src="' . $thumb . '" border="0" title="Click to enlarge" />';
				echo '<img src="' . $thumb . '" border="0" alt="' . htmlspecialchars($caption, ENT_COMPAT) . '" title="Click to enlarge" />';
				echo '</a>';
				echo '<div class="highslide-caption" >'."\n";
				echo $caption ."\n";
				if (file_exists($_SERVER['DOCUMENT_ROOT'] . $orig))
				{
					echo '<a href="' . $orig . '" title="High res">&prop;</a>'."\n"; 
				}
				echo "</div>   <!--- class=\"highslide-caption\" -->\n";  

				// show caption if flag is true
				if ($this->params['caption_flag'] && $caption != "") echo $image->get_value('description') ;
				echo "</div>   <!-- class=\"figure\" -->\n";
				$markup_string .= ob_get_contents();
				ob_end_clean();
			}
			else
			{
				$markup_string .= '<div id="spotlightimage">'."\n";
				$markup_string .= '<div class="figure">'."\n";
				ob_start();    
				echo '<img src="'.$full_image_name.'"/>';  
				$markup_string .= ob_get_contents();
				ob_end_clean();
				$markup_string .= '</div>';
				$markup_string .= '</div>';
			}
			
			echo $markup_string;

		}
		
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
