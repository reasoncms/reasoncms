<?php
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherTabWidgetModule';
	
	class LutherTabWidgetModule extends DefaultMinisiteModule
	{
		function init( $args = array() )
		{

		}
		function has_content()
		{
			return true;
		}
		function run()
		{
			$site_id = $this->site_id;
			$es = new entity_selector( $site_id );
			$es->add_type( id_of( 'tab_widget_type' ) );
			$es->add_right_relationship($this->cur_page->id(), relationship_id_of('page_to_tab_widget'));
			$es->add_rel_sort_field($this->cur_page->id(), relationship_id_of('page_to_tab_widget'));
			$es->set_order('rel_sort_order'); 
			$tws = $es->run_one();
			
			foreach( $tws AS $tw )
			{
				echo '<div id="tabs"><ul>'."\n";
				for ($i = 1; $i <= 5; $i++)
				{	
					$title = "tab_widget_title_".(string)$i;
					if ($tw->get_value($title) != '')
					{
						echo '<li><a href="#fragment-'.$i.'"><span>'.$tw->get_value($title).'</span></a></li>'."\n";
					}	
				}
				echo '</ul>'."\n";
				for ($i = 1; $i <= 5; $i++)
				{
					$content = "tab_widget_content_".(string)$i;
					if ($tw->get_value($content) != '')
					{
						echo '<div id="fragment-'.$i.'">'.$tw->get_value($content).'</div>'."\n";
					}
				}
				echo '<script>$( "#tabs" ).tabs();</script>'."\n";
			}
		}
	}
?>