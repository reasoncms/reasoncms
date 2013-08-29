<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
/**
 * Include parent class & register module with Reason
 */
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'Gallery2Module';
reason_include_once( 'minisite_templates/modules/generic3.php' );
reason_include_once( 'classes/sized_image.php' );
reason_include_once( 'function_libraries/image_tools.php' );
reason_include_once( 'classes/group_helper.php' );



/**
 * New image gallery. Based on Generic3, gets rid of pop-ups for images
 * and includes next and previous links on image page.
 *
 * @author Ben Cochran
 * @todo build in js-based slideshow
 * @todo figure out how randomness can be handled
 */
class Gallery2Module extends Generic3Module
{
	/**
	 * Sets url query fragment string
	 * @var string
	 */
	var $type_unique_name = 'image';
	
	/**
	 * Sets pagination default
	 * Set by parameters
	 * @var boolean
	 */
	var $use_pagination = true;
	
	/**
	 * Overrides generic3 default
	 * @var boolean
	 */
	var $show_list_with_details = false;

	/**
	 * The string used as the id of the module
	 * This might be better termed 'module_id'
	 * @var string
	 */	
	var $style_string = 'imageGallery';
	
	/**
	 * The string used to denote the item in the query string
	 * '_id' added to this string to build actual query key
	 * @var string
	 */	
	var $query_string_frag = 'image';
	
	/**
	 * The header used above the list of items when they are displayed below an item detail
	 * @var string
	 */	
	var $other_items = 'Other Images';
	
	/**
	 * The plural word used throughout the module to refer to more than one item
	 * @var string
	 */	
	var $plural_type_name = 'images';
	
	/**
	 * Enables filtering
	 * @var boolean
	 */
	var $use_filters = true;
	
	/**
	 * Sets what the filters should search
	 * @var string
	 */
	var $search_fields = array('chunk.content','meta.description','meta.keywords','chunk.author');
	
	/**
	 * Sets up the power search settings 
	 * @var array
	 */
	var $allowable_psearch_fields = array(
								'date' => array(
									'field' => 'dated.datetime',
									'cleanup_rule' => array('function' => 'turn_into_string'),
								)
							);
							
	/**
	 * Url of the next arrow image. Set in init
	 * @var string
	 */
	var $next_arrow_url;
	
	/**
	 * Url of the previous arrow image. Set in init
	 * @var string
	 */
	var $prev_arrow_url;
	
	/**
	 * True if there is only one item. Used by the "Back to list" link.
	 * (Because what's the use of going back to the list if the list takes
	 * you automatically back to the item)
	 * @var boolean
	 */
	var $only_one_item = false;
	
	/**
	 * The parameters that the module will allow
	 * @var array
	 */
	var $base_params = array(
		'limit_to_current_site'=>true,
		'filter_displayer'=>'gallery_specific.php',
		'pagination_displayer'=>'window.php',
		'use_pagination'=>true,
		'number_per_page' => 12,
		'entire_site'=>false,
		'use_relationship_sort' => false,
		'sort_order' => 'dated.datetime ASC, meta.description ASC, entity.id ASC',
		'show_dates_in_list' => false,
		'show_descriptions_in_list' => true,
		'date_format' => 'j F Y',
		'min_num_to_show_search' => 12,
		'thumbnail_height' => 0,
		'thumbnail_width' => 0,
		'thumbnail_crop' => '',
		'height' => 0,
		'width' => 0,
		'crop' => 0,
		'max_filters' => 3,
		'original_size_access_group' => '',
	);
	
	/**
	 * Are we using rel sort order or "regular" sorting
	 * This is set in parameters.
	 * @var boolean
	 */
	var $sorting_by_rel_sort_order = false;
	
	/**
	 * Array of id => array of image data such as thumbnail,
	 * width, etc (from the get_image_data() function)
	 * @var array
	 */
	var $image_array = array();
	
	/**
	 * Are we displaying the image description in the list.
	 * Set by parameters
	 * @var boolean
	 */
	var $use_desc_in_list = true;
	
	var $back_link_text = 'Thumbnails';
	var $make_current_page_link_in_nav_when_on_item = true;
	
	/**
	 * Add cleanup rules specific to the gallery module
	 * @return array
	 */
	function get_cleanup_rules()
	{
		$this->cleanup_rules['original_access'] = array('function'=>'turn_into_int');
		return parent::get_cleanup_rules();
	}
	
	/**
	 * Does the interacting with parameters, setting of defaults,
	 * and cleaning of the sort order string
	 */
	function additional_init_actions()
	{
		$this->prev_arrow_url = REASON_HTTP_BASE_PATH.'css/gallery2/image_gallery_arrow_prev.gif';
		$this->next_arrow_url = REASON_HTTP_BASE_PATH.'css/gallery2/image_gallery_arrow_next.gif';
		$this->parent->head_items->add_stylesheet( REASON_HTTP_BASE_PATH.'css/gallery2/gallery2.v2.css', '', true );
		$this->parent->head_items->add_javascript(JQUERY_URL, true);
		
		if( !isset($this->request['image_id']) )
		{
			$this->parent->head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/gallery2/next_page_link.v2.js');
		}
		$this->use_pagination = ($this->params['use_pagination']) ? true : false;
		$this->num_per_page = $this->params['number_per_page'];
		$this->use_dates_in_list = ($this->params['show_dates_in_list']) ? true : false;
		$this->use_desc_in_list = ($this->params['show_descriptions_in_list']) ? true : false;
		$this->date_format = $this->params['date_format'];
		$this->min_num_to_show_search = $this->params['min_num_to_show_search'];
		$this->init_sort_order();
	}
	
	function init_sort_order()
	{
		// Since rel_sort doesn't make sense on a site-wide gallery, we need to keep this from happening
		if (empty($this->params['sort_order']) || ($this->params['sort_order'] == 'rel' && $this->params['entire_site'] && !$this->is_virtual))
		{
			$sort_order_string = 'dated.datetime ASC, entity.id ASC';
		}
		elseif ($this->params['sort_order'] == 'rel')
		{
			$this->sorting_by_rel_sort_order = true;
		}
		else
		{
			$sort_order_string = $this->params['sort_order'];
		}

		if (!$this->sorting_by_rel_sort_order)
		{
			// If we use regular expressions to split the sort order string,
			// we'll be more likely to get real sort orders.
			$pattern = '/\b[A-Za-z]+\.[A-Za-z]+\b \b(?:ASC|DESC)\b/';
			// This odly-constructed while loop basically checks the given
			// sort order string against the regular expression; if it fails
			// to match, it assigns a default sort order string
			while (empty($matches))
			{
				preg_match_all($pattern, $sort_order_string, $matches);
				$sort_order_string = 'dated.datetime ASC, entity.id ASC';
			}
			$this->sort_order_string = implode(',',reset($matches));
		}
	}
	
	function calculate_height($image_height,$desc_length,$max_width)
	{
		if ($this->use_desc_in_list)
		{
			$desc_length = $desc_length*13/($max_width/7) + 6;
			if ($desc_length <= 45) $image_height += $desc_length;
			else $image_height += 45;
		}
		if ($this->use_dates_in_list) $image_height += 13;
		return $image_height;
	}
	
	/**
	 * Goes through the images that are selected by the es,
	 * gets data for them such as height, width, url, etc.
	 * Then, tries to estimate the height of the tallest image
	 * and its caption using some very unscientific measurements
	 * of font sizes and line lengths.
	 */
	function post_es_additional_init_actions()
	{
		if( isset($this->request['image_id']) )
			return;
		
		// The rest of this only applies if we are in list mode
		$largest_width = 0;
		$largest_height = 0;
		if(0 != $this->params['thumbnail_height'] or 0 != $this->params['thumbnail_width'])
		{
			foreach ($this->items as $image)
			{
				$rsi = new reasonSizedImage;
				$rsi->set_id($image->id());
				if(0 != $this->params['thumbnail_height']) $rsi->set_height($this->params['thumbnail_height']);
				if(0 != $this->params['thumbnail_width']) $rsi->set_width($this->params['thumbnail_width']);
				if('' != $this->params['thumbnail_crop']) $rsi->set_crop_style($this->params['thumbnail_crop']);
				$width = $rsi->get_image_width();
				if($width > $largest_width) $largest_width = $width;
			}
		}
		else
		{
			foreach ($this->items as $image)
			{
				$this->image_array[$image->id()] = $this->get_image_data($image);
			}
			
			
			foreach ($this->image_array as $image)
			{
				if (!empty($image['thumb']) && $image['thumb']['width'] > $largest_width)
					$largest_width = $image['thumb']['width'];
	
			}
		}
		if ($largest_width == 0) $largest_width = 125;
		$largest_width_with_padding = $largest_width + 20;
		
		$largest_height_with_text = 0;
		
		if(0 != $this->params['thumbnail_height'] or 0 != $this->params['thumbnail_width'])
		{
			foreach ($this->items as $image)
			{
				$rsi = new reasonSizedImage;
				$rsi->set_id($image->id());
				if(0 != $this->params['thumbnail_height']) $rsi->set_height($this->params['thumbnail_height']);
				if(0 != $this->params['thumbnail_width']) $rsi->set_width($this->params['thumbnail_width']);
				if('' != $this->params['thumbnail_crop']) $rsi->set_crop_style($this->params['thumbnail_crop']);
				$height_with_text = $this->calculate_height($rsi->get_image_height(),strlen($image->get_value('description')),$largest_width_with_padding);
				if($height_with_text > $largest_height_with_text) $largest_height_with_text = $height_with_text;
			}
		}
		else
		{
			
			foreach ($this->items as $image)
			{
				$this->image_array[$image->id()] = $this->get_image_data($image);
			}
			
			foreach ($this->image_array as $image)
			{
				if(!empty($image['thumb']))
				{
					$image_details = $image['thumb'];
				}
				elseif(!empty($image['full']))
				{
					$image_details = $image['full'];
				}
				else
				{
					trigger_error('Image has no thumb or full-sized image.');
					continue;
				}
				$height_with_text = $this->calculate_height($image_details['height'],strlen($image['description']),$largest_width_with_padding);
				if($height_with_text > $largest_height_with_text) $largest_height_with_text = $height_with_text;
			}
			
		}
		
		if ($largest_height == 0) $largest_height= 125;
		$largest_height_with_text = round($largest_height_with_text) + 15;		
		
		
		$css =  "\n\t".'#imageGalleryItemList li.item, li#imageGalleryNextPageItem {';
		$css .= "\n\t\t".'width: '.$largest_width_with_padding.'px;';
		//$css .= "\n\t\t".'height: '.$largest_height_with_text.'px;';
		$css .= "\n\t".'}';
		$this->parent->add_head_item('style', array('type' => 'text/css','media' => 'screen'),$css);

	}
	
	/**
	 * Runs through the list, showing the thumbnails.
	 */
	function do_list()
	{
		// Note that it is important not to have spaces between the items in order to use inline-block display
		echo '<ul id="imageGalleryItemList">';
		foreach( $this->items AS $item )
		{
			$this->show_list_item( $item );
		}
		
		if(
			$this->use_pagination && 
			( $this->show_list_with_details || empty( $this->current_item_id ) ) && 
			$this->total_pages > 1 && $this->total_pages != $this->request['page'] 
		)
		{
			$pages = $this->get_pages_for_pagination_markup();
			if (array_key_exists($this->request['page'] + 1,$pages))
				echo '<li id="imageGalleryNextPageItem"><a href="'.$pages[$this->request['page'] + 1]['url'].'" title="Page '.($this->request['page'] + 1).'">Next Page</a></li>';
		}
		echo '</ul>'."\n";
		
	}
	
	//Called on by list_items()
	//Calls on show_list_item_name(), show_list_item_desc(), show_list_item_pre()
	function show_list_item( $item ) // {{{
	{
		echo '<li class="item number'.$this->item_counter.'">' . "\n";
		$this->show_list_item_pre( $item );
		echo '<a href="' . $this->construct_link($item) . '" class="imageLink">';
		$this->show_list_item_name( $item );
		echo '</a>'."\n";
		$this->show_list_item_date( $item );
		$this->show_list_item_desc( $item );
		// Note that it is important not to have spaces between the items in order to use inline-block display
		echo '</li>';
		$this->item_counter++;
	} // }}}
	
	//Called on by show_list_item
	function show_list_item_name( $item )
	{
		if ($image_markup = $this->show_image($item))
			echo $image_markup;
		else
			echo '<span class="noImagePlaceholder">No Thumbnail</span>';
	}
	
	//Called on by show_list_item
	function show_list_item_date( $item )
	{
		if($this->use_dates_in_list && $item->get_value( 'datetime' ) )
		{
			echo '<p class="dateText">';
			echo '<a href="' . $this->construct_link($item) . '">';
			echo prettify_mysql_datetime( $item->get_value( 'datetime' ), $this->date_format );
			echo '</a></p>'."\n";
		}
	}
	
	//Called on by show_list_item
	function show_list_item_desc( $item )
	{
		if($this->use_desc_in_list && $item->get_value('description'))
		{
			echo '<p class="descText">';
			echo '<a href="' . $this->construct_link($item) . '">';
			echo $item->get_value('description');
			echo '</a></p>'."\n";
		}
	}
	
	//Called on by show_item
	function show_item_name( $item ) // {{{
	{
		echo '<h3 class="imageTitle">' . $item->get_value( 'description' ) . '</h3>'."\n";
	} // }}}
	
	//Called on by show_item()
	function show_item_content( $item ) // {{{
	{
		$this->show_sequence_number($item);
		$next_prev_array = $this->get_next_and_prev_images($item);
		if (!empty($next_prev_array['prev']))
		{
			echo '<div class="prevWrapper"><div class="thumbnail">';
			if ($prev_image_markup = $this->show_image($next_prev_array['prev']))
				echo $prev_image_markup;
			echo '</div><a href="'.$this->construct_link($next_prev_array['prev']).'" title="Previous: ' . $next_prev_array['prev']->get_value('description') . '"><img class="nav_arrow" src="'.$this->prev_arrow_url.'" width="80" height="80" alt="Previous image"></a></div>'."\n";
		}
		
		if (!empty($next_prev_array['next']))
		{
			echo '<div class="nextWrapper"><div class="thumbnail">';
			if ($next_image_markup = $this->show_image($next_prev_array['next']))
				echo $next_image_markup;
			echo '</div><a href="'.$this->construct_link($next_prev_array['next']).'" title="Next: ' . $next_prev_array['next']->get_value('description') . '"><img class="nav_arrow" src="'.$this->next_arrow_url.'" width="80" height="80" alt="Next image"></a></div>'."\n";
		}
		echo '<div class="imageWrapper">';
		echo $this->show_image( $item, false );
		echo '</div>'."\n";
		echo '<div class="imageCaptionWrapper">'."\n";
		if ($item->get_value( 'content' ))
			echo '<div class="fullDescription">' .  $item->get_value( 'content' ) . '</div>'."\n";
		if ($item->get_value( 'author' ))
			echo '<div class="author"><h4>Photo:</h4> ' .  $item->get_value( 'author' ) . '</div>'."\n";
		if ($item->get_value( 'keywords' ))
			echo '<div class="keywords"><h4>Keywords:</h4> ' .  $item->get_value( 'keywords' ) . '</div>'."\n";
		if ($item->get_value( 'datetime' ))
			echo '<div class="dateTime">' .  prettify_mysql_datetime($item->get_value( 'datetime' ), $this->date_format) . '</div>'."\n";
		
		$this->show_item_owner_site_info ( $item ); 
		$this->show_item_categories( $item );
		$this->show_item_original_size_link( $item );
		echo '</div>'."\n";
	} // }}}
	
	function show_item_owner_site_info ( &$item )
	{
		$owner_site_id = get_owner_site_id($item->id());
		if ($owner_site_id != $this->site_id)
		{
			$owner_site = new entity($owner_site_id);
			if ($owner_site->get_values())
			{
				echo '<div class="owner">From site: <a href="'.$owner_site->get_value( 'base_url' ).'">' . $owner_site->get_value( 'name' ) . '</a></div>'."\n"; 
			}
		}
	}
	
	function show_item_categories( &$item )
	{
		if ($cat_array = $this->get_categories_for_image($item->id()))
		{
			echo '<div class="categories"><h4>Categories:</h4> ' . implode(', ',$cat_array) . '</h4></div>';
		}
	}
	
	/**
	 * Get the markup for the link to the original size item
	 *
	 * @param object $item
	 * @return string
	 */
	function show_item_original_size_link( $item )
	{
		if(empty($this->params['original_size_access_group']))
			return '';
		
		$msg = '';
		if($url = $this->get_original_size_url($item))
		{
			switch($this->current_user_original_size_access( $item ))
			{
				case 'authentication_required':
					$msg = '<a href="'.REASON_LOGIN_URL.'?dest_page='.urlencode(carl_make_redirect(array('original_access'=>1))).'">Log in to see this image at higher resolution</a>';
					break;
				case 'not_authorized':
					if(!empty($this->request['original_access']))
						$msg = 'Sorry. You do not have permission to view the higher resolution version of this image.';
					break;
				case 'ok':
					$msg = '<a href="'.htmlspecialchars($url).'">View image at higher resolution</a>';
					break;
			}
		}
		
		if(!empty($msg))
			echo '<div class="originalSizeLink">'.$msg.'</div>'."\n";
	}
	
	/**
	 * Gets the categories associated with a given image
	 */
	function get_categories_for_image($image_id)
	{
		$es = new entity_selector();
		$es->add_type(id_of('category_type'));
		$es->add_right_relationship($image_id, relationship_id_of('image_to_category'));
		$categories = $es->run_one();
		foreach ($categories as $category)
		{
			$category_array[$category->id()] = $category->get_value('name');
		}
		if (!empty($category_array))
			return $category_array;
		else
			return false;
	}
	
	/**
	 * Get the url of the original image if it exists
	 *
	 * If it does not exist, this function will return an empty string.
	 *
	 * @param object $item
	 * @return string
	 */
	function get_original_size_url($item)
	{
		$path = reason_get_image_path($item, 'original');
		if(file_exists($path))
			return reason_get_image_url($item, 'original');
		return '';
	}
	
	/**
	 * Get access information about whether the current user can access a given image at its original size
	 * @return string 'no_group', 'authentication_required', 'ok', or 'not_authorized'
	 */
	function current_user_original_size_access( $item )
	{
		if(empty($this->params['original_size_access_group']))
			return 'no_group';
		if(!$group_id = id_of($this->params['original_size_access_group']))
		{
			trigger_error('Access group unique name parameter given in page type not a Reason entity.');
			return 'no_group';
		}
		$group = new entity($group_id);
		if($group->get_value('type') != id_of('group_type'))
		{
			trigger_error('Access group unique name does not belong to a valid Reason group.');
			return 'no_group';
		}
		$helper = new group_helper();
		$helper->set_group_by_entity($group);
		$result = $helper->is_username_member_of_group(reason_check_authentication());
		if(null === $result)
			return 'authentication_required';
		elseif(true === $result)
			return 'ok';
		else
			return 'not_authorized';
	}
	
	/**
	 * Uses generic3's new built in next and previous
	 * methods to get the next and previous items.
	 */
	function get_next_and_prev_images($item)
	{
		$return = array();
		
		if ($next_id = $this->get_next_item_id($item->id()))
		{
			$return['next'] = (isset($this->items[$next_id])) ? $this->items[$next_id] : new entity($next_id);
		}
		if ($prev_id = $this->get_previous_item_id($item->id()))
		{
			$return['prev'] = (isset($this->items[$prev_id])) ? $this->items[$prev_id] : new entity($prev_id);
		}
		if(!empty($return))
		{
			return $return;
		}
		else
		{
			$this->only_one_item = true;
			return false;
		}
	}
	
	//Called upon by run()
	//calls on construct_link()
	function show_back_link()
	{
	  if (!$this->only_one_item)
		  echo '<div class="back"><a href="'.$this->construct_link(NULL,array('page'=>$this->get_page_number_from_id($this->current_item_id))).'">'.$this->back_link_text.'</a></div>'."\n";
	}
	
	//called on by init()
	function alter_es() // {{{
	{
		if (!$this->sorting_by_rel_sort_order)
		{
			if ($this->params['entire_site'])
			{
				$this->es->set_site( $this->parent->site_id );
			}
			else
			{
				$this->es->add_right_relationship( $this->parent->cur_page->id(), relationship_id_of('minisite_page_to_image') );
			}
			
			$this->es->set_order($this->sort_order_string);
		}
		else
		{
			$this->es->add_right_relationship( $this->parent->cur_page->id(), relationship_id_of('minisite_page_to_image') );
			$this->es->add_rel_sort_field( $this->parent->cur_page->id(), relationship_id_of('minisite_page_to_image'), 'rel_sort_order');
			// order first by rel_sort_order if that is not defined second criteria is dated.datetime ASC and in case of same datetimes, lastly by entity.id- this keeps pages that change to gallery pages reasonably predictable
			$this->es->set_order( 'rel_sort_order ASC, dated.datetime ASC, entity.id ASC' );
		}
		
	} // }}}
	
	/**
	 * Get information about an image, such as
	 * height, width, url, etc.
	 *
	 * This should be replaced by a new image
	 * class that's nicer and cleaner.
	 */
	function get_image_data($image)
	{
		$values = $image->get_values();
		$id = $image->id();
		$image = $values;
		
		$tn_name = reason_get_image_filename($id, 'tn');
		$full_name = reason_get_image_filename($id);
		
		if( file_exists( PHOTOSTOCK . $tn_name ) )
		{
			$return_array['thumb']['url'] = WEB_PHOTOSTOCK . $tn_name;
			list($return_array['thumb']['width'],$return_array['thumb']['height']) = getimagesize( PHOTOSTOCK . $tn_name );
		}
		if( file_exists( PHOTOSTOCK . $full_name ) )
		{
			$return_array['full']['url'] = WEB_PHOTOSTOCK . $full_name;
			list($return_array['full']['width'],$return_array['full']['height']) = getimagesize( PHOTOSTOCK . $full_name );
		}
		
		$return_array['description'] = $image['description'];
		return $return_array;
	}
	
	/**
	 * Returns an img tag for a given image or
	 * it's thumbnail.
	 *
	 * Also should be replaced by a new image class.
	 */
	
	function show_image_markup($image_url,$height,$width,$alt,$class,$mod_time)
	{
		return '<img src="'.$image_url.'?'.$mod_time.'" width="'.$width.'" height="'.$height.'" alt="'.htmlentities($alt).'" class="'.$class.'" />';
	}
	
	function show_image($image, $thumbnail = true)
	{
		if($thumbnail)
		{
			if(0 != $this->params['thumbnail_height'] or 0 != $this->params['thumbnail_width'])
			{
				$rsi = new reasonSizedImage;
				$rsi->set_id($image->id());
				if(0 != $this->params['thumbnail_height']) $rsi->set_height($this->params['thumbnail_height']);
				if(0 != $this->params['thumbnail_width']) $rsi->set_width($this->params['thumbnail_width']);
				if('' != $this->params['thumbnail_crop']) $rsi->set_crop_style($this->params['thumbnail_crop']);

				$width = $rsi->get_image_width();
				$height = $rsi->get_image_height();
				$image_url = $rsi->get_url();
				$image_path = $rsi->get_file_system_path_and_file_of_dest();
			}
			else
			{
				$image_path = reason_get_image_path($image,'tn');
				$image_url = reason_get_image_url($image, 'tn');
				if(!file_exists($image_path))
				{
					$image_path = reason_get_image_path($image);
					$image_url = reason_get_image_url($image);
				}
				list($width,$height) = getimagesize($image_path);
			}
			$class = 'thumbnail';
			
		}
		elseif(!$thumbnail)
		{
			if(0 != $this->params['height'] or 0 != $this->params['width'])
			{
				$rsi = new reasonSizedImage;
				$rsi->set_id($image->id());
				if(0 != $this->params['height']) $rsi->set_height($this->params['height']);
				if(0 != $this->params['width']) $rsi->set_width($this->params['width']);
				if('' != $this->params['crop']) $rsi->set_crop_style($this->params['crop']);

				$width = $rsi->get_image_width();
				$height = $rsi->get_image_height();
				$image_url = $rsi->get_url();
				$image_path = $rsi->get_file_system_path_and_file_of_dest();

				
			}
			else
			{
				$image_path = reason_get_image_path($image);
				list($width,$height) = getimagesize($image_path);
				$image_url = reason_get_image_url($image);
			}
			$class = 'mainImage';
			
		}
	
		if(file_exists($image_path))
		{
			$alt = $image->get_value('description');
			if(!$alt)
			{
				$alt = $image->get_value('keywords');
				if(!$alt) $alt = $image->get_value('name');
			}
			
			$mod_time = filemtime($image_path);
			return $this->show_image_markup($image_url,$height,$width,$alt,$class,$mod_time);
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Makes sure the selected entity id would turn up
	 * in the list of this entity selector.
	 */
	function further_checks_on_entity( $entity )
	{
		$es = carl_clone($this->es);
		$es->add_relation('entity.id = '.$entity->id());
		$es->set_num(1);
		$es->set_start(0);
		$check = $es->run_one();
		if(empty($check))
			return false;
		return true;
	}
	
	/**
	 * Used by the pagination displayer.
	 * Returns an array of distinct dates represented
	 * in the whole set of data.
	 */
	function get_distinct_date_array()
	{
		
		$dates_es = carl_clone($this->pre_user_input_es);
		$dates_es->set_order('dated.datetime ASC');
		$dates_es->limit_tables(array('dated'));
		$dates_es->limit_fields(array('entity.id','dated.datetime'));
		$dates_es->set_start(0);
		$dates_es->set_num(false);
		$items = $dates_es->run_one();
		
		foreach ($items as $item)
		{
			$ts = get_unix_timestamp( $item->get_value( 'datetime' ) );
			$dt = date('Y-m-d',$ts);
			$ts = get_unix_timestamp( $dt );
			if( $ts > 0 )
				$return_array[ $dt . '*'] = date('D, M j, Y',$ts);
		}
		if (!empty($return_array))
			return $return_array;
		else
			return false;
	}
	
	/**
	 * Shows the given item's position in the format "#__ of __"
	 */
	function show_sequence_number($item)
	{
		if (($current = $this->get_item_position($item->id())) && ($total = $this->total_count))
		{
			echo '<div class="sequenceNum">'."\n";
			echo '#<em>' . $current . '</em> of <em>' . $total . '</em>'."\n";
			echo '</div>'."\n";
		}
	}
	
	/**
	 * Uses the "archived" version of the es from before
	 * applying filters to get a count of entities.
	 */
	function get_total_num_images_before_user_input()
	{
		$total_es = carl_clone($this->pre_user_input_es);
		$total_es->set_start(0);
		$total_es->set_num(false);
		return $total_es->get_one_count();
	}
	
	/**
	 * Decides wether or not search should be shown.
	 * Basically, doesn't show search if the number of items is
	 * less than min_num_to_show_search.
	 */
	function show_search()
	{
		if (($this->get_total_num_images_before_user_input() < $this->min_num_to_show_search ) && (empty($this->request['search'])) )
			return false;
		else
			return true;
	}
	
	/**
	 * Gets 'nice' crumb text by stripping tags from the description.
	 */
	function get_crumb_text(&$item)
	{
		return strip_tags( $item->get_value('description') );
	}
}

?>
