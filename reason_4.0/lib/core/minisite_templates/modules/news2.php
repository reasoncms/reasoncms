<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
  	/**
 	 * Include parent class and register module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/generic3.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'News2Module';

/**
 * A module that lists news items on the current site.
 *
 * Note: this module is deprecated. Use the publications framework instead.
 *
 * @deprecated
 */
class News2Module extends Generic3Module
{	
	var $style_string = 'news';
	var $use_pagination = true;
	var $query_string_frag = 'story';
	var $num_per_page = 5;
	var $pagination_prev_next_texts = array('previous'=>'Newer','next'=>'Older');
	var $use_dates_in_list = true;
	var $show_list_with_details = false;
	// No items text
	var $no_items_text = 'There are no news items available on this site.';
	var $submodules = array('news_content'=>array(	'title'=>'',
													'title_tag'=>'',
													'wrapper_class'=>'text',
													'date_format' => 'j F Y',
												),
							'news_images'=>array(	'title'=>'Images',
													'title_tag'=>'h4',
													'wrapper_class'=>'images',
												),
							'news_assets'=>array(	'title'=>'Related Documents',
													'title_tag'=>'h4',
													'wrapper_class'=>'assets',
												),
							'news_related'=>array(	'title'=>'Related Stories',
													'title_tag'=>'h4',
													'wrapper_class'=>'relatedNews',
												),
							'news_categories_submodule'=>array(	'title'=>'Categories',
													'title_tag'=>'h4',
													'wrapper_class'=>'categories',
												),
						);
	var $class_vars_pass_to_submodules = array();
	var $has_feed = true;

	function init( $args = array() )
	{
		parent::init( $args );
		trigger_error('The News2Module is deprecated and will be removed from the Reason Core before RC1. Transition pages using this module to use publications instead - a migrator is available in /scripts/developer_tools/publication_migrator.php');	
	}
	
	function set_type()
	{
		$this->type = id_of('news');
	}
	function alter_es() // {{{
	{
		$this->es->set_order( 'dated.datetime DESC' );
		$this->es->add_relation( 'status.status = "published"' );
	} // }}}
	function show_list_item_name( $item )
	{
		echo $item->get_value( 'release_title' );
	}
	function show_item_name( $item ) // {{{
	{
		echo '<h3>' . $item->get_value( 'release_title' ) . '</h3>'."\n";
	} // }}}
	
	//overloaded generic3 function
	function show_list_item_pre( $item )
	{
		$es = new entity_selector( $this->parent->site_id );
		$es->description = 'Finding teaser image for news item';
		$es->add_type( id_of('image') );
		$es->add_right_relationship( $item->id(), relationship_id_of('news_to_teaser_image') );
		$es->set_num (1);
		$result = $es->run_one();
		if (!empty($result))
		{
			echo '<div class="teaserImage">';
			show_image( reset($result), true,false,false );
			echo '</div>';
		}
	}
	
	function show_item_content( $item ) // {{{
	{
		$this->run_submodules($item);
	} // }}}
	function run_submodules($item)
	{
		$submodules_output = array();
		$site = new entity($this->parent->site_id);
		foreach($this->submodules as $sub_name=>$params)
		{
			$sub = new $sub_name();
			$sub->pass_params($params);
			$sub->pass_site($site);
			if(!empty($this->class_vars_pass_to_submodules))
			{
				$add_vars = array();
				foreach($this->class_vars_pass_to_submodules as $var)
				{
					if(!empty($this->$var))
					{
						$add_vars[$var] = $this->$var;
					}
				}
				if(!empty($add_vars))
				{
					$sub->pass_additional_vars($add_vars);
				}
			}
			$sub->init($this->request, $item);
			if($sub->has_content())
			{
				$submodules_output[$sub_name] = $sub->get_content();
			}
		}
		if(!empty($submodules_output))
		{
			if(count($submodules_output) > 1)
			{
				$additional_class_text = ' multiple';
			}
			else
			{
				$additional_class_text = '';
			}
			echo '<div class="submodules'.$additional_class_text.'">'."\n";
			foreach($submodules_output as $sub_name=>$sub_out)
			{
				echo '<div class="'.$this->submodules[$sub_name]['wrapper_class'].'">'."\n".$sub_out.'</div>'."\n";
			}
			echo '</div>'."\n";
		}
	}
}
/**
 * @deprecated
 */
class submodule
{
	var $request = array();
	var $params = array();
	var $site;
	var $addional_vars = array();
	function pass_params($params)
	{
		foreach($params as $key=>$value)
		{
			if(array_key_exists($key,$this->params))
			{
				$this->params[$key] = $value;
			}
		}
	}
	function pass_site($site)
	{
		$this->site = $site;
	}
	function pass_additional_vars($vars)
	{
		$this->additional_vars = $vars;
	}
	function init($request, $news_item = NULL)
	{
		if(!empty($request))
		{
			$this->request = $request;
		}
	}
	function has_content()
	{
		return true;
	}
	function get_content()
	{
		trigger_error('this must be overloaded');
	}
}
/**
 * @deprecated
 */
class news_content extends submodule
{
	var $item;
	var $params = array('date_format'=>'j F Y');
	function init($request, $news_item = NULL)
	{
		parent::init($request);
		$this->item = $news_item;
	}
	function has_content()
	{
		if($this->item->get_value('content') || $this->item->get_value('description'))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	function get_content()
	{
		$ret = '';
		if( $this->item->get_value( 'datetime' ) )
		{
			$ret .= '<p class="date">'.prettify_mysql_datetime($this->item->get_value( 'datetime' ), $this->params['date_format']).'</p>';
		}
		if( $this->item->get_value( 'author' ) )
		{
			$ret .= '<p class="author">By '.$this->item->get_value( 'author' ).'</p>';
		}
		if( $this->item->get_value( 'content' ) )
		{
			$ret .= str_replace(array('<h3>','</h3>'), array('<h4>','</h4>'), $this->item->get_value( 'content' ) );
		}
		else
		{
			$ret .= $this->item->get_value('description');
		}
		return $ret;
	}
}
/**
 * @deprecated
 */
class news_images extends submodule
{
	var $images = array();
	var $params = array( 'title'=>'Images', 'title_tag'=>'h4' );
	var $die_without_thumbnail = false;
	var $show_popup_link = true;
	var $show_description = true;
	var $additional_text = '';
	var $textonly = false;
	
	function init($request, $news_item = NULL)
	{
		parent::init($request);
		$es = new entity_selector();
		if(method_exists ( $es, 'set_env' ))
		{
			$es->set_env( 'site' , $this->site->id() );
		}
		$es->description = 'Selecting images for news item';
		$es->add_type( id_of('image') );
		$es->add_right_relationship( $news_item->id(), relationship_id_of('news_to_image') );
		$es->add_rel_sort_field( $news_item->id(), relationship_id_of('news_to_image') );
		$es->set_order('rel_sort_order');
		$this->images = $es->run_one();
		if(!empty($this->request['textonly']))
		{
			$this->textonly = $this->request['textonly'];
		}
	}
	function has_content()
	{
		if(!empty($this->images))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	function get_content()
	{
		$list_parts = array();
		foreach($this->images as $image)
		{
			ob_start();
			show_image( $image, $this->die_without_thumbnail, $this->show_popup_link, $this->show_description, $this->additional_text, $this->textonly );
			$list_parts[] = ob_get_contents();
			ob_end_clean();
		}
		$title = '<'.$this->params['title_tag'].'>'.$this->params['title'].'</'.$this->params['title_tag'].'>';
		$content = '<ul>'."\n".'<li>'.implode('</li>'."\n".'<li>',$list_parts).'</li>'."\n".'</ul>'."\n";
		return $title."\n".$content;
	}
}
/**
 * @deprecated
 */
class news_assets extends submodule
{
	var $assets = array();
	var $params = array( 'title'=>'Assets', 'title_tag'=>'h4' );
	function init($request, $news_item = NULL)
	{
		$es = new entity_selector();
		$es->description = 'Selecting assets for news item';
		$es->add_type( id_of('asset') );
		$es->add_right_relationship( $news_item->id(), relationship_id_of('news_to_asset') );
		$this->assets = $es->run_one();
	}
	function has_content()
	{
		if(!empty($this->assets))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	function get_content()
	{
		$list_parts = array();
		reason_include_once( 'function_libraries/asset_functions.php' );
		$title = '<'.$this->params['title_tag'].'>'.$this->params['title'].'</'.$this->params['title_tag'].'>';
		$content = make_assets_list_markup( $this->assets, $this->site );
		return $title."\n".$content;
	}
}
/**
 * @deprecated
 */
class news_related extends submodule // ummm there is not even a news_to_news relationship
{
	var $related = array();
	var $params = array( 'title'=>'Related News Items', 'title_tag'=>'h4' );
	function init($request, $news_item = NULL)
	{
		// uncomment if news_to_news relationship is created
		//$es = new entity_selector();
		//$es->description = 'Selecting related news for news item';
		//$es->add_type( id_of('news') );
		//$es->add_right_relationship( $news_item->id() , relationship_id_of( 'news_to_news' ) );
		//$es->add_relation( 'status.status = "published"' );
		//$this->related = $es->run_one();
	}
	function has_content()
	{
		if(!empty($this->related))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	function get_content()
	{
		$list_parts = array();
		foreach($this->related as $related_item)
		{
			$list_parts[] = '<a href="?item_id='.$related_item->id().'">'.$related_item->get_value('release_title').'</a>';
		}
		$title = '<'.$this->params['title_tag'].'>'.$this->params['title'].'</'.$this->params['title_tag'].'>';
		$content = '<ul>'."\n".'<li>'.implode('</li>'."\n".'<li>',$list_parts).'</li>'."\n".'</ul>'."\n";
		return $title."\n".$content;
	}
}
////////////////////////
//Categories Submodule
///////////////////////
/**
* Display categories associated with a news item
* @deprecated
*/
class news_categories_submodule extends submodule
{
	var $categories = array();
	var $params = array( 'title'=>'Categories', 'title_tag'=>'h4' );
	var $textonly = false;
	
	function init($request, $news_item = NULL)
	{
		parent::init($request);
		$es = new entity_selector();
		$es->description = 'Selecting categories for news item';
		$es->add_type( id_of('category_type') );
		$es->set_env('site',$this->site->id());
		$es->add_right_relationship( $news_item->id(), relationship_id_of('news_to_category') );
		$es->set_order( 'entity.name ASC' );
		$this->categories = $es->run_one();
		
		if(!empty($this->request['textonly']))
		{
			$this->textonly = $this->request['textonly'];
		}
	}
	
	function has_content()
	{
		if(!empty($this->categories))
			return true;
		else
			return false;
	}

	function get_content()
	{
		$list_parts = array();
		$title = '<'.$this->params['title_tag'].'>'.$this->params['title'].'</'.$this->params['title_tag'].'>';
		$content = '<ul>';
		foreach($this->categories as $category)
		{
			$link = '?filters[1][type]=category&filters[1][id]='.$category->id();
			if($this->textonly)
			{
				$link .= '&amp;textonly=1';
			}
			$content .= '<li><a href="'.$link.'">'.$category->get_value('name').'</a></li>';
		}
		$content .= '</ul>';
		return '<a name="comments">'.$title.'</a>'."\n".$content;
	}
}
?>
