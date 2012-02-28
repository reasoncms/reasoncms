<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 	/**
 	 * Include parent class and register module with Reason
 	 */
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'RandNewsMinisiteModule';
	reason_include_once( 'minisite_templates/modules/news.php' );
	reason_include_once('function_libraries/image_tools.php');

	/**
	 * A minisite module that on any given request lists a single random news item on the site
	 *
	 * Note: this module is deprecated. Use the publications framework instead.
	 * If you want this functionality, set this up as a related publication, set num_per_page to 1,
	 * and set related_order to "RAND()".
	 *
	 * @deprecated
	 */
	class RandNewsMinisiteModule extends NewsMinisiteModule
	{
		var $num_per_page = 1;
		
		function init ($args = array())
		{
			$note = 'Note: the News Rand module is deprecated and will go away in the near future. Use the publications framework instead. If you want this functionality, set this up as a related publication, set num_per_page to 1, and set related_order to "RAND()".';
			trigger_error($note);
			echo '<div class="warning">'.$note.'</div>';
			parent::init($args);
		}
		function list_news_no_issue() // {{{
		{
			
			$v = new rand_news_viewer;
			$v->request = &$this->request;
			$v->num_per_page = $this->num_per_page;
			$v->textonly = $this->textonly;
			$v->init( $this->site_id , id_of( 'news' ) );
			$v->do_display();
		} // }}}
	}
	class rand_news_viewer extends no_issue_news_viewer
	{
		function alter_values() // {{{
		{
			$this->es->set_order( 'dated.datetime DESC' );
			$this->es->add_relation( 'status.status = "published"' );
			$this->num = $this->es->get_one_count();
			$this->num_pages = ceil( $this->num / $this->num_per_page );
			$this->randomize_page();
		} // }}}
		function show_item( $item ) // {{{
		{
			$content = $item->get_value( 'content' );
			$desc = $item->get_value( 'description' );
			
			if (empty($this->textonly))
			{
				$img = new entity_selector();
				$img->description = 'Selecting images for news item';
				$img->add_type( id_of('image') );
				$img->add_right_relationship( $item->id(), relationship_id_of('news_to_image') );
				$img->add_rel_sort_field( $item->id(), relationship_id_of('news_to_image') );
				$img->set_order('rel_sort_order');
				$item->images = $img->run_one();
				
				echo "\n<div class='newsItem'>\n";
				if (!empty($item->images))
				{
					$die = isset( $this->die_without_thumbmail ) ? $this->die_without_thumbnail : false;
					$popup = isset( $this->show_popup_link ) ? $this->show_popup_link : true;
					$img_desc = isset( $this->description ) ? $this->description : true;
					$text = isset( $this->additional_text ) ? $this->additional_text : "";
					echo '<div class="NewsListImg">';
					foreach( $item->images AS $id => $image )
					{
						echo '<img src="'.WEB_PHOTOSTOCK. reason_get_image_filename( $image->id() ) .'" width="'.$image->get_value( 'width' ).'" height="'.$image->get_value( 'height' ).'" alt="'.str_replace('"', "'", $image->get_value( 'description' )).'"/>';
						break;
					}
					echo "</div>\n";
				}
			}
			if ( !empty( $desc ) )
				echo "<div class='newsItemDesc'>" . $desc . "</div>\n";
			
			if ( !empty( $content ) )
			{
				echo '<p class="moreLink"><a href="?';
				echo 'story_id=' . $item->id();
				if ( !empty( $this->request[ 'page' ] ) )
					echo '&amp;page=' . $this->request[ 'page' ];
				if (!empty($this->textonly))
					echo '&amp;textonly=1';
				echo '" class="newsItemLink">';
				echo 'read full story';
				echo '</a></p>';
			}
			echo "</div>\n";
		} // }}}
		function display() // {{{
		{
			$this->show_all_items();
		} // }}}
		function randomize_page()
		{
			$this->page = rand(1, $this->num_pages);
		}
		function show_all_items() // {{{
		{
			$row = 0;
			reset( $this->values );
			while( list( $id, $item ) = each( $this->values ) )
			{
				if( ($row % $this->rows_per_sorting) == 0 )
					$this->show_sorting();
				$this->show_item( $item );
				$row++;
			}
		} // }}}
	}
?>
