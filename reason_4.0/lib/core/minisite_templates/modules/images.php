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
	);
	
	function alter_es() // {{{
	{
		$this->es->set_order( 'dated.datetime ASC' );
		$this->es->set_env( 'site' , $this->site_id );
		$this->es->add_right_relationship( $this->parent->cur_page->id(), relationship_id_of('minisite_page_to_image') );
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
		if ( empty($this->parent->textonly) )
		{
			echo '<img src="'.WEB_PHOTOSTOCK.$item->id().'.'.$item->get_value('image_type').'?cb='.$item->get_value('last_modified').'" width="'.$item->get_value('width').'" height="'.$item->get_value('height').'" alt="'.htmlentities($item->get_value('description')).'" />';
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
			echo '<a href="'.WEB_PHOTOSTOCK.$item->id().'.'.$item->get_value('image_type').'" title="View image">'.$caption.'</a>'."\n";
		}echo '</li>'."\n";
	}
}
?>
