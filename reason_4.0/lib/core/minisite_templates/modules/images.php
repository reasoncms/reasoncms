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
		'crop' => '', // 'fill' or 'fit'
		);
	
	function init( $args = array() )
	{
		if(!empty($this->params['num_per_page']))
			$this->num_per_page = (integer) $this->params['num_per_page'];
		parent::init();
	}
	function alter_es() // {{{
	{
		if($this->params['sort_order'] == 'rel')
		{
			$this->es->add_rel_sort_field( $this->page_id, relationship_id_of('minisite_page_to_image'), 'rel_sort_order');
			$this->es->set_order( 'rel_sort_order ASC, dated.datetime ASC, meta.description ASC, entity.id ASC' );
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
		$this->es->add_right_relationship( $this->page_id, relationship_id_of('minisite_page_to_image') );
	} // }}}
	function show_list_item( $item ) // {{{
	{
		if($item->get_value('content'))
		{
			$caption = $item->get_value('content');
		}
		else
		{
			$caption = $item->get_value('description');
		}
		
		if($this->params['width'] || $this->params['height'])
		{
			$rsi = new reasonSizedImage;
			$rsi->set_id($item->id());
			if($this->params['height'])
				$rsi->set_height($this->params['height']);
			if($this->params['width'])
				$rsi->set_width($this->params['width']);
			if($this->params['crop'])
				$rsi->set_crop_style($this->params['crop']);
			$image_url = $rsi->get_url();
			$width = $rsi->get_image_width();
			$height = $rsi->get_image_height();
		}
		else
		{
			$image_url = reason_get_image_url($item).'?cb='.urlencode($item->get_value('last_modified'));
			$width = $item->get_value('width');
			$height = $item->get_value('height');
		}
		
		echo '<li>';
		if ( empty($this->textonly) )
		{
			echo '<img src="'.$image_url.'" width="'.$width.'" height="'.$height.'" alt="'.htmlspecialchars(strip_tags($item->get_value('description')), ENT_QUOTES).'" />';
			if($this->params['show_captions'])
			{
				echo '<div class="caption">'.$caption.'</div>'."\n";
			}
			if($this->params['show_authors'] && $item->get_value('author'))
			{
				echo '<div class="author">Photo: '.$item->get_value('author').'</div>'."\n";
			}
		}
		else
		{
			echo '<a href="'.$image_url.'" title="View image">'.$caption.'</a>'."\n";
		}
		echo '</li>'."\n";
	}
}
?>
