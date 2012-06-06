<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
/**
 * Include parent class and register module with Reason
 */
reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'classes/sized_image.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ImageSidebarModule';

/**
 * A minisite module that displays image thumbnails
 *
 * Thumbnails have a link to a popup window that displays the full-sized image
 *
 * @todo Use lightbox-like display method instead of popup; make sure that individual photos still have uniquely linkable URLs
 */
class ImageSidebarModule extends DefaultMinisiteModule
{
	var $es;
	var $images;

	var $acceptable_params = array(
		// Maximum number of images to display (undefined = all)
		'num_to_display' => '',
		// Skip this number of images in the pool before choosing the display set
		'num_to_skip' => 0,
		// Show captions with images
		'caption_flag' => true,
		// Display images in random order
		'rand_flag' => false,
		// SQL order by string to define custom sort
		'order_by' => '',
		// Scale images to these proportions (0 = default size)
		'thumbnail_width' => 0,
		'thumbnail_height' => 0,
		// How to crop the image to fit the size requirements; 'fill' or 'fit'
		'thumbnail_crop' => '',
		// Set this to display images associated with a page other than the one
		// the module is running on.
		'alternate_source_page_id' => '',
	);

	function init( $args = array() )
	{
		parent::init( $args );
		$this->select_images();
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
		$die = isset( $this->die_without_thumbnail ) ? $this->die_without_thumbnail : false;
		$popup = isset( $this->show_popup_link ) ? $this->show_popup_link : true;
		$desc = isset( $this->description ) ? $this->description : true;
		$text = isset( $this->additional_text ) ? $this->additional_text : "";
		
		if ( !empty($this->textonly) )
			echo '<h3>Images</h3>'."\n";
		
		foreach( $this->images AS $id => $image )
		{
			$show_text = $text;
			if( !empty( $this->show_size ) )
				$show_text .= '<br />('.$image->get_value( 'size' ).' kb)';
			echo "<div class=\"imageChunk\">";
			
			if($this->params['thumbnail_width'] != 0 or $this->params['thumbnail_height'] != 0)
			{
				$rsi = new reasonSizedImage();
				if(!empty($rsi))
				{
					$rsi->set_id($image->id());
					if($this->params['thumbnail_width'] != 0)
					{
						$rsi->set_width($this->params['thumbnail_width']);
					}
					if($this->params['thumbnail_height'] != 0)
					{
						$rsi->set_height($this->params['thumbnail_height']);
					}
					if($this->params['thumbnail_crop'] != '')
					{
						$rsi->set_crop_style($this->params['thumbnail_crop']);
					}
					$image = $rsi;
				}
			}
			
			
			if ($this->params['caption_flag'] == false)
				show_image( $image, $die, $popup, false, $show_text, $this->textonly,false );
			else
				show_image( $image, $die, $popup, $desc, $show_text, $this->textonly,false );
				
				
			echo "</div>\n";
		}
	}
	
	function select_images()
	{
		if ($this->params['alternate_source_page_id'])
		{
			$page_id = $this->params['alternate_source_page_id'];
			if (!($site_id = get_owner_site_id($page_id)))
				$site_id = $this->site_id;
		} else {
			$page_id = $this->cur_page->id();
			$site_id = $this->site_id;	
		}
		
		$this->es = new entity_selector();
		$this->es->description = 'Selecting images for sidebar';
		$this->es->add_type( id_of('image') );
		$this->es->set_env( 'site' , $site_id );
		$this->es->add_right_relationship( $page_id, relationship_id_of('minisite_page_to_image') );
		if ($this->params['rand_flag']) $this->es->set_order('rand()');
		elseif (!empty($this->params['order_by'])) $this->es->set_order($this->params['order_by']);
		else
		{
			$this->es->add_rel_sort_field( $page_id, relationship_id_of('minisite_page_to_image') );
			$this->es->set_order('rel_sort_order');
		}
		if (!empty($this->params['num_to_display'])) $this->es->set_num( (!empty($this->params['num_to_skip'])) ? ($this->params['num_to_display'] + $this->params['num_to_skip']) : $this->params['num_to_display'] );
		$this->images = $this->es->run_one();
		if ( !empty($this->images) && !empty($this->params['num_to_skip']))
		{
			$this->images = array_slice($this->images, $this->params['num_to_skip'], NULL, true);
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