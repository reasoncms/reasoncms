<?php

// FAQ Module; extends the Generic 2 Module -- July 2004 MR
// now extends generic3

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'FAQModule';
	reason_include_once( 'minisite_templates/modules/generic3.php' );

	class FAQModule extends Generic3Module
	{
		var $type_unique_name = 'faq_type';
		var $style_string = 'faq';
		var $other_items = 'Other FAQs';
		var $query_string_frag = 'faq';
		var $use_filters = true;
		var $filter_types = array(	'category'=>array(	'type'=>'category_type',
														'relationship'=>'faq_to_category',
													),
								);
		var $search_fields = array('entity.name','meta.description','meta.keywords','chunk.content','chunk.author');
		var $acceptable_params = array(
			'audiences'=>array(),
			'limit_to_current_site'=>true,
		);
		var $has_feed = true;
		var $feed_link_title = 'Subscribe to this feed for updates to this FAQ';
		var $make_current_page_link_in_nav_when_on_item = true;

		function show_item_name( $item ) // {{{
		{
			echo '<h3 class="faqName">' . $item->get_value( 'description' ) . '</h3>'."\n";
		} // }}}
		function alter_es() // {{{
		{
			$this->es->set_order( 'entity.last_modified DESC' );
			if(!empty($this->params['audiences']))
			{
				$aud_ids = array();
				foreach($this->params['audiences'] as $audience)
				{
					$aud_id = id_of($audience);
					if($aud_id)
					{
						$aud_ids[] = $aud_id;
					}
					else
					{
						trigger_error($audience.' is not a unique name; skipping this audience');
					}
				}
				if(!empty($aud_ids))
				{
					$this->es->add_left_relationship($aud_ids, relationship_id_of('faq_to_audience'));
				}
			}
		} // }}}
		
		function show_list_item_name( $item )
		{
			echo $item->get_value( 'description' );
		}
		
		function show_list_item_desc( $item )
		{
			if($item->get_value('content'))
			{
				$desc_array = explode(' ',strip_tags($item->get_value('content')));
				echo '<div>'.implode(' ', array_slice( $desc_array, 0, 15)).'...</div>';
			}
		}
		function show_item_content( $item ) // {{{
		{
			echo '<div class="answer">';
			echo $item->get_value( 'content' );
			$owner = $item->get_owner();
			if($item->get_value('author') || $item->get_value( 'datetime' ) || $item->get_value( 'keywords' ) || $owner->id() != $this->site_id)
			{
				echo '<ul class="meta">';
				if($item->get_value('author') || $item->get_value( 'datetime' ))
				{
					echo '<li>';
					if($item->get_value('author'))
					{
						echo $item->get_value('author');
						if($item->get_value( 'datetime' ))
							echo ', ';
					}
					if($item->get_value( 'datetime' ))
						echo prettify_mysql_datetime( $item->get_value( 'datetime' ), "j F Y" );
					echo '</li>'."\n";
				}
				if($owner->id() != $this->site_id)
				{
					$url = $owner->get_value('base_url');
					if($this->textonly)
						$url .= '?textonly=1';
					echo '<li>FAQ courtesy of <a href="'.$url.'">'.$owner->get_value('name').'</a></li>'."\n";
				}
				if($item->get_value( 'keywords' ))
					echo '<li class="hide">Keywords: '.strip_tags($item->get_value( 'keywords' )).'</li>'."\n";
				echo '</ul>'."\n";
			}
			echo '</div>'."\n";
		} // }}}
	}
?>
