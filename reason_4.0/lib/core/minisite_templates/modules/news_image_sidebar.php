<?php
/**
 * @package reason
 * @subpackage minisite_templates
 */
	
	/**
	 * Include parent class; register module with Reason
	 */
	reason_include_once( 'minisite_templates/modules/image_sidebar.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'newsImageSidebarModule';

/**
 * A minisite module that displays images associated with the news item requested in story_id
 * @deprecated
 */
class newsImageSidebarModule extends ImageSidebarModule
{
	var $cleanup_rules = array('story_id' => array ('function' => 'turn_into_int'));

	function init( $args = array() ) // {{{
	{
		if (!empty($this->request['story_id']))
		{
			$this->es = new entity_selector();
			$this->es->description = 'Selecting images for sidebar';
			$this->es->add_type( id_of('image') );
			$this->es->set_env( 'site' , $this->site_id );
			$this->es->add_right_relationship( $this->request['story_id'], relationship_id_of('news_to_image') );
			if ($this->params['rand_flag']) $this->es->set_order('rand()');
			elseif (!empty($this->params['order_by'])) $this->es->set_order($this->params['order_by']);
			else
			{
				$this->es->add_rel_sort_field( $this->request['story_id'], relationship_id_of('news_to_image') );
				$this->es->set_order('rel_sort_order');
			}
			if (!empty($this->params['num_to_display'])) $this->es->set_num($this->params['num_to_display']);
			$this->images = $this->es->run_one();
		}
		else $this->images = '';
	} // }}}
}
?>
