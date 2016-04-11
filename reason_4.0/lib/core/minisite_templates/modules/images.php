<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 	/**
 	 * Include parent class and register module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/generic3.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'imageModule';
	
	reason_include_once( 'function_libraries/images.php' );
	reason_include_once( 'classes/sized_image.php' );

/**
 * A minisite module that displays the normal-sized images attached to the current page
 */
class imageModule extends Generic3Module
{
	var $type_unique_name = 'image';
	var $style_string = 'images';
	var $use_pagination = true;
	var $num_per_page = 12;
	var $jump_to_item_if_only_one_result = false;
	var $acceptable_params = array(
		'show_captions'=>true,
		'show_authors'=>true,
		'limit_to_current_site'=>true,
		'max_num' => false, // false or integer
		'sort_order' => 'rel', // Either a sort_order value (like "datetime ASC) or the special value "rel", meaning sort by page relationship
		'num_per_page' => 0,
		'width' => 0,
		'height' => 0,
		'crop' => '', // 'fill', 'fit', 'max_height', or 'max_width'
		'markup' => '', // 'default' or otherwise
		'target_page_unique_name' => '', // '' or otherwise
		'limit_by_related_types'=>array(),
		'stealth_mode' => false,
	);
	var $default_markup = 'minisite_templates/modules/images_markup/default.php';
	var $target_page;

	protected $_markup;
	
	function init( $args = array() )
	{
		if(!empty($this->params['num_per_page']))
			$this->num_per_page = (integer) $this->params['num_per_page'];
		if(isset($this->params['stealth_mode']))
			$this->stealth_mode = (boolean) $this->params['stealth_mode'];
		parent::init();
	}
	function alter_es() // {{{
	{
		if($this->params['sort_order'] == 'rel')
		{
			$this->es->add_rel_sort_field( $this->get_target_page()->id(), relationship_id_of('minisite_page_to_image'), 'rel_sort_order');
			$this->es->set_order( 'rel_sort_order ASC, dated.datetime ASC, meta.description ASC, entity.id ASC' );
		}
		elseif($this->params['sort_order'] == 'rel_reverse')
		{
			$this->es->add_rel_sort_field( $this->get_target_page()->id(), relationship_id_of('minisite_page_to_image'), 'rel_sort_order');
			$this->es->set_order( 'rel_sort_order DESC, dated.datetime DESC, meta.description DESC, entity.id DESC' );
		}
		else
		{
			$this->es->set_order( $this->params['sort_order'] );
		}
		if($this->params['max_num'])
		{
			$this->es->set_num($this->params['max_num']);
		}
		$this->es->set_env( 'site' , $this->site_id );
		
		$this->es->add_right_relationship( $this->get_target_page()->id(), relationship_id_of('minisite_page_to_image') );		
	} // }}}
	
	function get_target_page()
	{
		if(!isset($this->target_page))
		{
			if($this->params['target_page_unique_name'])
			{
				if(reason_unique_name_exists($this->params['target_page_unique_name']))
				{
				    $target_page = new entity(id_of($this->params['target_page_unique_name']));
				    if($target_page->get_value('type') == id_of('minisite_page'))
			        	$this->target_page = $target_page;
				    else
    				{
			  	    	trigger_error('No page found with the unique name '.$this->params['target_page_unique_name'].'. Using 	images from the current page.');
    			    }
				}
			}
			if(empty($this->target_page))
				$this->target_page = new entity($this->page_id);
		}
		return $this->target_page;
	}
	function do_list()
	{
		$markup = $this->get_markup_object();
		$params = array();
		if(isset($this->params))
		{
			$params = $this->params;
		}
		$params['current_page_id'] = $this->page_id;
		$params['target_page_id'] = $this->get_target_page()->id();
		if(!empty($markup))
		{
			echo $markup->get_markup($this->items,$params);
		}
	}

	/**
	 * Get a markup object
	 *
	 * @return object
	 */
	function get_markup_object()
	{
		if(isset($this->_markup))
			return $this->_markup;
		
		if(isset($this->params['markup']))
		{
			if(!empty($this->params['markup']))
			{
				$path = $this->params['markup'];
			}
			else
			{
				$path = $this->default_markup;
			}
			if(reason_file_exists($path))
			{
				reason_include_once($path);
				if(!empty($GLOBALS['images_markup'][$path]))
				{
					if(class_exists($GLOBALS['images_markup'][$path]))
					{
						$markup = new $GLOBALS['images_markup'][$path];
						if($markup instanceof imagesListMarkup)
							$this->_markup = $markup;
						else
							trigger_error('Markup does not implement imagesListMarkup interface');
					}
					else
					{
						trigger_error('No class with name '.$GLOBALS['images_markup'][$path].' found');
					}
				}
				else
				{
					trigger_error('Images markup not properly registered at '.$path);
				}
			}
			else
			{
				trigger_error('No markup file exists at '.$path);
			}
		}
		else
		{
			trigger_error('Unrecognized markup type ('.$type.')');
		}
		if(!isset($this->_markup))
			$this->_markup = false;
		return $this->_markup;
	}

}
?>
