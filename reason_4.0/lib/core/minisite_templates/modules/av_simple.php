<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 	/**
 	 * Include parent class and register module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/generic3.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'avSimpleModule';
	
	reason_include_once( 'classes/av_display.php' );
	reason_include_once( 'function_libraries/url_utils.php' );
	reason_include_once( 'classes/kaltura_shim.php' );
	reason_include_once('classes/media_work_helper.php');
	reason_include_once( 'classes/media_work_displayer.php' );

/**
 * A minisite module that displays the normal-sized images attached to the current page
 */
class avSimpleModule extends Generic3Module
{
	var $type_unique_name = 'av';
	var $style_string = 'avSimple';
	var $use_pagination = true;
	var $num_per_page = 12;
	var $jump_to_item_if_only_one_result = false;
	var $acceptable_params = array(
		'show_descriptions'=>false,
		'show_authors'=>false,
		'limit_to_current_site'=>true,
		'limit_to_current_page'=>true,
		'max_num' => false, // false or integer
		'num_per_page' => 0,
		'width' => 640,
		'height' => 0,
		'sort_direction'=>'DESC', // Normally this page shows items in reverse chronological order, but you can change this to ASC for formward chronological order
		'sort_field'=>'dated.datetime',
		'relationship_sort'=>true, // Says whether the module should pay attention to the 'sortable' nature of the minisite_page_to_av allowable relationship
	);
	
	function init( $args = array() )
	{
		if(!empty($this->params['num_per_page']))
			$this->num_per_page = (integer) $this->params['num_per_page'];
		$head_items = $this->get_head_items();
		$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'modules/av/av_simple.css');
		parent::init();
	}
	function alter_es() // {{{
	{
		if($this->params['limit_to_current_page'])
		{
			$this->es->add_right_relationship( $this->parent->cur_page->id(), relationship_id_of('minisite_page_to_av') );
		}
		if ( !$this->params['relationship_sort'] || !$this->params['limit_to_current_page'])
		{
			$this->es->set_order( $this->params['sort_field'].' '.$this->params['sort_direction'] );
		}
		else
		{
			$this->es->add_rel_sort_field($this->parent->cur_page->id(), relationship_id_of('minisite_page_to_av'));
			$this->es->set_order('rel_sort_order ASC');
		}
		$this->es->add_relation( 'show_hide.show_hide = "show"' );
		$this->es->add_relation( '(media_work.transcoding_status = "ready" OR ISNULL(media_work.transcoding_status) OR media_work.transcoding_status = "")' );
		$this->es->set_env( 'site' , $this->site_id );
	} // }}}
	function show_list_item( $item ) // {{{
	{
		echo '<li>';
		echo '<h4>'.$item->get_value('name').'</h4>'."\n";
		echo '<div class="media">'."\n";
		if ($item->get_value('integration_library') == 'kaltura')
		{
			$displayer = new MediaWorkDisplayer();
			if($this->params['height'])
				$displayer->set_height($this->params['height']);
			if($this->params['width'])
				$displayer->set_width($this->params['width']);
			$displayer->set_media_work($item);
			echo $displayer->get_iframe_markup();
		}
		else
		{
			$files = $this->get_av_files( $item, 1 );
			$first = reset($files);
			$avd = new reasonAVDisplay();
			$avd->disable_automatic_play_start();
			echo $avd->get_embedding_markup($first);
		}
		echo '</div>'."\n";
		if($this->params['show_descriptions'] && $item->get_value('description'))
		{
			echo '<div class="description">'.$item->get_value('description').'</div>'."\n";
		}
		if($this->params['show_authors'] && $item->get_value('author'))
		{
			echo '<div class="author">By '.$item->get_value('author').'</div>'."\n";
		}
		echo '</li>'."\n";
	}
	function get_av_files( $item, $num = 0 ) // {{{
	{
		$avf = new entity_selector();
		$avf->add_type( id_of('av_file' ) );
		$avf->add_right_relationship( $item->id(), relationship_id_of('av_to_av_file') );
		$avf->set_order('av.media_format ASC, av.av_part_number ASC');
		if($num)
			$avf->set_num($num);
		return $avf->run_one();
	}
}
?>
