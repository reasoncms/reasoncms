<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 	/**
 	 * Include parent class and register module with Reason
 	 */
	reason_include_once( 'classes/slideshow_standalone.php' );
	reason_include_once( 'minisite_templates/modules/image_sidebar.php' );
	reason_include_once('function_libraries/image_tools.php');

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ImageSlideshowModule';
	
	/**
	 * A minisite module that displays a js-based slideshow of images attached to the page
	 *
	 * @todo get css and inline javascript into external files.
	 * @todo make me work properly if only one dimensions is set (height or width) - right now dynamic determination is not based on image aspect ratio.
	 * @todo make flexsider width, height, aspect ratio stuff dynamic and determined in javascript.
	 * @todo with changes, try to avoid using reason sized image in the init phase.
	 */
	class ImageSlideshowModule extends DefaultMinisiteModule
	{
		/**
		 * An array of the slideshow's image entities
		 * @var array
		 */
		var $images;
		var $slideshow;
		
		var $acceptable_params = array(
			'slideshow_type' => 'auto', 
			'width' => 0,
			'height' => 0,
			'crop' => '',
			//'force_image_enlargement' => false,
			'rand_flag' => false, 
			'num_to_display' => '',
			'caption_flag' => true,
			'show_short_caption' => true,
			'show_long_caption' => true,
			'show_author' => false,
			'order_by' => '',
			'alternate_source_page_id' => '', 
			// The animation_type 'slide' is buggy in FlexSlider.  We're not going to support it right now.
			//'animation_type' => 'slide',
			//'slide_direction' => 'vertical',
			'slide_timer' => 5,
			'show_direction_nav' => true,
			//'show_control_nav' => false,
		);
		
		function init( $args = array() )
		{
			parent::init( $args );
			//Add necesary head items
			$head_items = $this->get_head_items();

			$slideshowConfig = $this->params;
			$slideshowConfig["headItemsObj"] = $head_items;
			$this->slideshow = new SlideshowStandalone($slideshowConfig);

			$this->select_images();

			if (empty($this->images))
			{
				//return here since there will not be a slide show with no images.
				return;
			}
			$this->slideshow->setImages($this->images);
		}
		
		function has_content()
		{
			// Don't bother running the slideshow if no images exist
			if (empty($this->images))
			{
				return false;
			}
			return true;
		}
		
		function run_text_only() { echo $this->slideshow->getTextOutput(); }
		function run_full_graphics() { echo $this->slideshow->getNormalOutput(); }

		function run()
		{	
			if (!empty($this->textonly))
			{
				$this->run_text_only();
			}
			else
			{
				$this->run_full_graphics();
			}
		}
		
		/**
		 * Identify the images that should be displayed
		 */
		function select_images()
		{
			// Initialize the images with appropriate entity selector properties
			
			$page_id = $this->page_id;
			if (!empty($this->params['alternate_source_page_id']))
			{
				$page_id = $this->params['alternate_source_page_id'];
				if (!($site_id = get_owner_site_id($page_id))) $site_id = $this->site_id;
			}
			else
			{
				$page_id = $this->cur_page->id();
				$site_id = $this->site_id;
			}
			
			$es = new entity_selector();
			$es->add_type(id_of('image'));
			$es->set_env('site', $site_id);
			$es->add_right_relationship($page_id, relationship_id_of('minisite_page_to_image'));
			if ($this->params['rand_flag']) $es->set_order('rand()');
			elseif (!empty($this->params['order_by'])) $es->set_order($this->params['order_by']);
			else
			{
				$es->add_rel_sort_field( $page_id, relationship_id_of('minisite_page_to_image') );
				$es->set_order('rel_sort_order');
			}
			if (!empty($this->params['num_to_display']))
			{
				$es->set_num($this->params['num_to_display']);
			}
			$this->images = $es->run_one();			
		}
	}
?>
