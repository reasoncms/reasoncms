<?php
/**
 * @package reason
 * @subpackage content_previewers
 */
/**
 * Include dependencies & register previewer with Reason
 */
$GLOBALS[ '_content_previewer_class_names' ][ basename( __FILE__) ] = 'av_previewer';
reason_include_once('classes/media/factory.php');	
	
/**
 * A content previewer for media works
 *
 */
class av_previewer extends default_previewer
{
	protected $previewer_modifier;
	
	function init( $id , &$page )
	{
		parent::init($id , $page);
		
		$this->previewer_modifier = MediaWorkFactory::media_work_previewer_modifier($this->_entity);
		if ($this->previewer_modifier)
		{
			$this->previewer_modifier->set_previewer($this);
			$this->previewer_modifier->set_head_items($this->admin_page->head_items);
		}
	}

	function display_entity()
	{
		if ($this->previewer_modifier)
		{
			$this->previewer_modifier->display_entity();
		}
		else
		{
			$this->start_table();
			$this->show_all_values( $this->_entity->get_values() );
			$this->end_table();
		}
	}
}
?>
