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
		
		echo '<li>';
		if ( empty($this->textonly) )
		{
			echo '<img src="'.reason_get_image_url($item).'?cb='.urlencode($item->get_value('last_modified')).'" width="'.$item->get_value('width').'" height="'.$item->get_value('height').'" alt="'.htmlspecialchars(strip_tags($item->get_value('description')), ENT_QUOTES).'" />';
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
			echo '<a href="'.reason_get_image_url($item).'" title="View image">'.$caption.'</a>'."\n";
		}
		echo '</li>'."\n";
	}
}
?>
