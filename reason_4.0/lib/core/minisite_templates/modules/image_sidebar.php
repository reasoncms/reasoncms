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
reason_include_once('function_libraries/image_tools.php');
reason_include_once( 'classes/api/api.php' );
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
	var $image_detail_head_items;

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
	
	var $cleanup_rules = array('image_id' => 'turn_into_int');
	
	static function setup_supported_apis()
	{
		$image_url_api = new ReasonAPI(array('html'));
		self::add_api('image_detail', $image_url_api);
	}

	function image_may_be_shown($id)
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
		
		$es = new entity_selector();
		$es->add_type( id_of('image') );
		$es->set_env( 'site' , $site_id );
		$es->add_right_relationship( $page_id, relationship_id_of('minisite_page_to_image') );
		$es->add_relation('entity.id = '. addslashes($id));
		$result = $es->run_one();
		
		//echo 'given id='.$id;
		
		if(! empty($result))
		{
			//echo 'returning true';
			return true;
		}
		else
		{
			//echo 'returning false';
			return false;
		}
	}

	function init( $args = array() )
	{
		parent::init( $args );
		
		$api = $this->get_api();
		if (!empty($api) && $api->get_name() == 'image_detail')
		{
			//Do standalone mode initialization
			$this->image_detail_head_items = new HeadItems();
			$this->image_detail_head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'modules/image_sidebar/styles.css');
			if (defined(UNIVERSAL_CSS_PATH))
			{
				$this->image_detail_head_items->add_stylesheet(UNIVERSAL_CSS_PATH);
			}
		}
		else
		{
			//Do standard initialization
			$head_items = $this->get_head_items();
			$this->select_images();
			if (count($this->images) > 0)
			{
				if (defined(UNIVERSAL_CSS_PATH))
				{
					$head_items->add_stylesheet(UNIVERSAL_CSS_PATH);
				}
				$head_items->add_javascript(JQUERY_URL, true);
				$head_items->add_javascript(WEB_JAVASCRIPT_PATH . 'jquery.reasonAjax.js');
				$head_items->add_javascript(REASON_HTTP_BASE_PATH . 'modules/image_sidebar/image_sidebar.js');
				$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'modules/image_sidebar/styles.css');
				$head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH.'FancyBox/source/jquery.fancybox.js');
				$head_items->add_stylesheet(REASON_PACKAGE_HTTP_BASE_PATH.'FancyBox/source/jquery.fancybox.css');
			}
		}
	}
	
	function has_content()
	{
		if( $this->images )
			return true;
		else
			return false;
	}
	
	function run_api() 
	{
		$api = $this->get_api();
		if ($api->get_name() == 'image_detail')
		{
			if ($api->get_content_type() == 'html') $api->set_content($this->get_image_detail_content());
			$api->run();
		}
		else parent::run_api(); // support other apis defined by parents
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
			echo '<div class="imageChunk '.$this->get_api_class_string().'">';
			
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
				show_image( $image, $die, $popup, false, $show_text, $this->textonly, false, $this->get_image_url($image) );
			else
				show_image( $image, $die, $popup, $desc, $show_text, $this->textonly, false, $this->get_image_url($image) );
				
			echo "</div>\n";
		}
	
	}
	
	function get_image_detail_content()
	{	
		$buf = '';
		if (!empty($this->request['image_id']) && is_numeric($this->request['image_id']))
		{
			$image = new entity($this->request['image_id']);

			if ($this->image_may_be_shown($this->request['image_id']))
			{
				$img_info = $this->get_image_detail_image_info($image);
				
				$buf.= '<!DOCTYPE html>'."\n";
				$buf.= '<html>'."\n";
				
				$buf.= '<head>'."\n";
				$buf.= '<title>'. strip_tags($img_info['title']) .'</title>'."\n";
				$buf.= $this->image_detail_head_items->get_head_item_markup();
				$buf.= '</head>'."\n";
				
				$buf.= '<body>'."\n";
				$buf.= '<div id="standalone_content" style="min-width:'.$img_info['width'].'px ;min-height:'.$img_info['height'].'px ;">'."\n";
				$buf.= '<img width="'.$img_info['width'].'" height="'.$img_info['height'].'" class="standalone_image" src="' . $img_info['url'] . '"/>'."\n";
				
				$buf.= '<div style="width: '.$img_info['width'].'px;" class="standalone_image_info">'."\n";
				$buf.= '<div class="caption">'.$img_info['caption'].'</div>'."\n";
				
				if (!empty($img_info['author']) && $img_info['author'] != 'n/a')
					$buf.= '<p class="author">(<em>Photo by: '.strip_tags($img_info['author']).'</em>)</p>'."\n";
				
				
				$buf.= '</div>'."\n"; //standalone_image_info
				$buf.= '</div>'."\n"; //standalone_content
				$buf.= '</body>'."\n";
				$buf.= '</html>'."\n";
				return $buf;
			}
		}
		//Error:
		http_response_code(404);
		$buf.= '<!DOCTYPE html>'."\n";
		$buf.= '<html>'."\n";
		$buf.= '<head>'."\n";
		$buf.= '<title>404 Error</title>'."\n";
		$buf.= '</head>'."\n";
		$buf.= '<body>'."\n";
		$buf.='<p>This image has been removed from this page. <a href ="./">Return</a> to page.</p>'."\n";
		$buf.= '</body>'."\n";
		$buf.= '</html>'."\n";
		return $buf;
	}
	
	function get_image_detail_image_info($image)
	{
		$image_info = array();
		
		if( is_array( $image ) )
		{
			$image_entity = new entity($image['id']);
		}
		elseif( is_object( $image ) )
		{
			if ('reasonSizedImage' == get_class($image) )
			{
				$image_entity = new entity($image->get_id());
			}
			else
			{
				$image_entity = $image;
			}
		}
		
		$img_description = $image_entity->get_value('description');
		$img_width = $image_entity->get_value('width');
		$img_height = $image_entity->get_value('height');
		
		$img_url = reason_get_image_url($image_entity->id());
		
		$img_author = $image_entity->get_value('author');
		
		$title = $image_entity->get_value('description') ? $image_entity->get_value('description') : 'Image';
		$image_caption = $image_entity->get_value('content') ? $image_entity->get_value('content') : $image_entity->get_value('description');
		
		$image_info = array('title' => $title, 'caption' => $image_caption, 'url' => $img_url, 'author' => $img_author, 'width' => $img_width, 'height' => $img_height);
		
		return $image_info;
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
	
	function get_image_url($image)
	{
		if( is_array( $image ) )
		{
			$id = $image['id'];
		}
		elseif( is_object( $image ) )
		{
			if ('reasonSizedImage' == get_class($image) )
			{
				$id = $image->get_id();
			}
			else
			{
				$id = $image->id();
			}
		}
		
		$params = array(
			'module_api' => 'image_detail',
			'module_identifier' => 'module_identifier-' . $this->identifier,
			'image_id' => $id,
		);
		$link = carl_make_link($params);
		
		$info = $this->get_image_detail_image_info($image);
		
		// this would return just a link to the image with no metadata
		//return $info['url'];
		
		return $link;
	}
}
?>