<?php
	reason_include_once( 'minisite_templates/modules/news2_mini.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'news2MiniRandomModule';

class news2MiniRandomModule extends News2MiniModule
{
	var $style_string = 'newsMiniRandom';
	var $num_per_page = 1;
	var $use_other_page_name_as_module_title = false;
	var $jump_to_item_if_only_one_result = false;
	var $has_feed = false;
	
	function alter_es()
	{
		parent::alter_es();
		$this->es->set_order('RAND()');
	}
}
?>
