<?php
	/* Top news, from the main news site. mr, 9/23/2004 */

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'NewsTopModule';
	reason_include_once( 'minisite_templates/modules/default.php' );

	/**
	 * @deprecated
	 */
	class NewsTopModule extends DefaultMinisiteModule
	{
		var $items = array();
		
		function init ( $args = array() )	 // {{{
		{
			parent::init( $args );
			trigger_error('NewsTop is deprecated and will be removed from the Reason Core before RC1. Transition pages using this module to use publications instead - a migrator is available in /scripts/developer_tools/publication_migrator.php');
			
			$now = date('Y-m-d H:i:s');
			$es = new entity_selector(id_of('media_relations'));
			$es->description = 'Selecting top news';
			$es->add_type( id_of('news') );
			$es->set_num( 5 );
			$es->set_order('dated.datetime DESC' );
			$es->add_relation('status.publish_start_date < "'.$now.'"');
			$es->add_relation('status.publish_end_date > "'.$now.'"');
			$this->items = $es->run_one();
		} // }}}
		function has_content() // {{{
		{
			if( empty($this->items) )
			{
				return false;
			}
			else
				return true;
		} // }}}
		function run()
		{
			$news_site = new entity(id_of('media_relations'));
			$base_url = $news_site->get_value('base_url');
			echo '<div id="topNews">'."\n";
			echo '<h4>Top News</h4>'."\n";
			echo '<ul>';
			foreach($this->items as $item)
			{
				$url = $base_url.'?content=content&amp;module=&amp;id='.$item->id();
				echo '<li><a href="'.$url.'">'.$item->get_value('release_title').'</a></li>';
			}
			echo '</ul>'."\n";
			echo '<p><a href="'.$base_url.'">More News</a></p>'."\n";
			echo '</div>'."\n";
		}
	}
?>
