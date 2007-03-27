<?php
	reason_include_once( 'minisite_templates/modules/news2.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'newsByCategoryModule';

class newsByCategoryModule extends News2Module
{
	var $style_string = 'newsByCategory';
	var $no_items_text = 'There are no news items available on this page.';

	function alter_es() // {{{
	{
		parent::alter_es();
		$es = new entity_selector( $this->parent->site_id );
		$es->description = 'Selecting categories for this page';
		$es->add_type( id_of('category_type') );
		$es->set_env('site',$this->parent->site_id);
		$es->add_right_relationship( $this->parent->cur_page->id(), relationship_id_of('page_to_category') );
		$es->set_num( 1 );
		$cats = $es->run_one();
		$cat = current($cats);
		if ($cat) $this->es->add_left_relationship( $cat->id(), relationship_id_of('news_to_category') );
	} // }}}
}
?>
