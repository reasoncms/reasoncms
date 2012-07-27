<?php
//reason_include_once( 'minisite_templates/modules/publication/list_item_markup_generators/default.php' );
reason_include_once( 'minisite_templates/modules/publication/markup_generator.php' );

/**
 * Generates markup for list items in a related news list.  
 *
 * @author Nathan White
 *
 */

class SpotlightListItemMarkupGenerator extends PublicationMarkupGenerator
{
	//variables needed to be passed from the publication module
	var $variables_needed = array( 	'use_dates_in_list', 
		'date_format', 
		'item',
		'link_to_full_item',
		'item_images',
		'teaser_image',
		'cur_page',
		'site_id',
		);

	function MinimalListItemMarkupGenerator ()
	{
	}
	
	function run ()
	{			
		if (get_theme($this->passed_vars['site_id'])->get_value('name') == 'luther2010')
		{
			if ($this->passed_vars['cur_page']->get_value( 'custom_page' ) == 'luther2010_home'
				|| $this->passed_vars['cur_page']->get_value( 'custom_page' ) == 'luther2010_music')
			{
				if ($this->passed_vars['cur_page']->get_value( 'custom_page' ) == 'luther2010_music')
				{
					$this->markup_string .= '<section class="spotlight" role="group">'."\n";					
					$this->markup_string .= '<header class="red-stripe"><h1><span>Spotlight</span></h1></header>'."\n";
				}
				$this->markup_string .= '<article class="highlight">'."\n";
				//$this->markup_string .= $this->get_title_markup();
				$this->markup_string .= $this->get_description_markup();
				$this->markup_string .= $this->get_image_markup();
				$full_link = $this->passed_vars['link_to_full_item'];
				$full_link = preg_replace("|http(s)?:\/\/\w+\.\w+\.\w+|", "", $full_link);
				$this->markup_string .= '<nav class="button read-more">'."\n";
				$this->markup_string .= '<ul><li><a href="'.$full_link.'">Read more &gt;</a></li></ul>'."\n";
				$this->markup_string .= '</nav>'."\n";
				$this->markup_string .= '</article>'."\n";
				$this->markup_string .= '<nav class="button view-all">'."\n";
				$this->markup_string .= '<ul><li><a href="/spotlightarchives">View all spotlights &gt;</a></li></ul>'."\n";
				$this->markup_string .= '</nav>'."\n";
				if ($this->passed_vars['cur_page']->get_value( 'custom_page' ) == 'luther2010_music')
				{
					$this->markup_string .= '</section> <!-- class="spotlight" role="group" -->'."\n";	
				}
			}
			else 
			{
				$this->markup_string .= '<section class="spotlight" role="group">'."\n";
				if ($this->passed_vars['cur_page']->get_value( 'custom_page' ) == 'luther2010_sports')
				{	
					$this->markup_string .= '<header class="blue-stripe"><h1><span>Spotlight</span></h1></header>'."\n";
				}
				else 
				{
					$this->markup_string .= '<header class="red-stripe"><h1><span>Spotlight</span></h1></header>'."\n";
				}
				$this->markup_string .= '<article class="highlight">'."\n";
				$this->markup_string .= $this->get_description_markup();
				$this->markup_string .= $this->get_teaser_image_markup();
				$full_link = $this->passed_vars['link_to_full_item'];
				$full_link = preg_replace("|http(s)?:\/\/\w+\.\w+\.\w+|", "", $full_link);
				$this->markup_string .= '<nav class="button read-more">'."\n";
				$this->markup_string .= '<ul><li><a href="'.$full_link.'">Read more &gt;</a></li></ul>'."\n";
				$this->markup_string .= '</nav>'."\n";
				$this->markup_string .= '</article>'."\n";
				if ($this->passed_vars['cur_page']->get_value( 'custom_page' ) == 'luther2010_sports')
				{
					$this->markup_string .= '<nav class="button view-all">'."\n";
					$this->markup_string .= '<ul><li><a href="/sports/spotlightarchives">View all spotlights &gt;</a></li></ul>'."\n";
					$this->markup_string .= '</nav>'."\n";
				}
				$this->markup_string .= '</section> <!-- class="spotlight" role="group" -->'."\n";			
			}		
		}
		else
		{
			$this->markup_string .= '<div id="spotlight">'."\n";
			if ($this->passed_vars['cur_page']->get_value( 'custom_page' ) != 'luther_primaryLRC')
			{
	
				$this->markup_string .= '<div class="column span-17">'."\n";
				//$this->markup_string .= $this->get_date_markup();
				$this->markup_string .= $this->get_image_markup();
				//$this->markup_string .= $this->get_pre_markup();
	                	$this->markup_string .= '</div class="column span-17">'."\n";
	                	$this->markup_string .= '<div class="column span-12 append-1">'."\n";
		 		//$this->markup_string .= $this->get_title_markup();
		 		$this->markup_string .= $this->get_description_markup();
	                	$this->markup_string .= '</div class="column span-12 append-1">'."\n";
			}
			else
			{
		 		$this->markup_string .= $this->get_description_markup();
				$this->markup_string .= $this->get_image_markup();
			}
			$this->markup_string .= '</div id="spotlight">'."\n";
		}
	}
	
/////
// show_list_item methods
/////
	function get_pre_markup()
	{
		return $this->get_teaser_image_markup();
	}

	function get_image_markup()
	{
		$markup_string = '';
		$image = $this->passed_vars['item_images'];
		if (!empty($image))
		{
		$id = reset($image)->get_value('id');
		$imgtype = reset($image)->get_value('image_type');
		$full_image_name = WEB_PHOTOSTOCK.$id.'.'.$imgtype;	
			//$markup_string .= '<div class="image">';
			ob_start();	
			//reason_get_image_url(reset($image), 'standard');
			//print_r(array_values( $image));
			if (get_theme($this->passed_vars['site_id'])->get_value('name') == 'luther2010')
			{
				echo '<figure><img src="'.$full_image_name.'" width="235"/></figure>';
			}
			else
			{
				echo '<img src="'.$full_image_name.'"/>';
			}
			//show_image( reset($image), true,true,false );
			$markup_string .= ob_get_contents();
			ob_end_clean();
			//$markup_string .= '</div>';
		} 
		return $markup_string;
	}
	
	function get_teaser_image_markup()
	{
		$markup_string = '';
		$image = $this->passed_vars['item_images'];
		if (!empty($image))
		{
			$id = reset($image)->get_value('id');
			$imgtype = reset($image)->get_value('image_type');
			if ($this->passed_vars['cur_page']->get_value( 'custom_page' ) == 'luther2010_sports')
			{
				$thumbnail_image_name = WEB_PHOTOSTOCK.$id.'.'.$imgtype;
			}
			else
			{
				$thumbnail_image_name = WEB_PHOTOSTOCK.$id.'_tn.'.$imgtype;
			}
			//$markup_string .= '<div class="teaserImage">';
			ob_start();
			if (get_theme($this->passed_vars['site_id'])->get_value('name') == 'luther2010')
			{
				echo '<figure><img src="'.$thumbnail_image_name.'" width="83"/></figure>';
			}
			else	
			{
				show_image( reset($image), true,false,false );
			}
			$markup_string .= ob_get_contents();
			ob_end_clean();
			//$markup_string .= '</div>';
		} 
		return $markup_string;
	}
	
	function get_description_markup()
	{
		$markup_string = '';
		if (get_theme($this->passed_vars['site_id'])->get_value('name') == 'luther2010')
		{
			$item = $this->passed_vars['item'];				
			if ($s = $item->get_value('description'))
			{
				$s = preg_replace("|\\n|", "", $s);  // remove any line breaks
				$s = preg_replace("|</?strong>|", "", $s);
				$markup_string .= '<header>'."\n";
				$markup_string .= '<hgroup>'."\n";
				preg_match_all("|<p>(.*?)</p>|", $s, $match, PREG_SET_ORDER);
				
				if ($match[0][1])
				{
					$markup_string .= '<h1 class="name">' . $match[0][1] . '</h1>'."\n";	
				}
				if ($match[1][1])
				{
					$markup_string .= '<h2 class="adr">' . $match[1][1] . '</h2>'."\n";	
				}
				$markup_string .= '</hgroup>'."\n";
				$markup_string .= '</header>'."\n";
				
				$markup_string .= '<div class="profile">'."\n";
				if ($match[2][1])
				{
					$markup_string .= $match[2][1]."\n";	
				}
				$markup_string .= '</div>'."\n";
			}			
		}
		else
		{
			if ($this->passed_vars['cur_page']->get_value( 'custom_page' ) == 'luther_primaryLRC')
			{
				$markup_string .= '<h2>Luther<br/>Spotlight</h2>'."\n";
			}
			else
			{
				$markup_string .= '<h2>Luther Spotlight</h2>'."\n";
			}
	
			$item = $this->passed_vars['item'];
			if($item->get_value('description'))
				//return '<div class="desc">'.$item->get_value('description').'</div>'."\n";
				$markup_string .= '<div class="desc">'."\n";
			$markup_string .= $item->get_value('description')."\n";
	
			$full_link = $this->passed_vars['link_to_full_item'];
			$full_link = preg_replace("|http(s)?:\/\/\w+\.\w+\.\w+|", "", $full_link);
			$markup_string .= '<a href="'.$full_link.'">read spotlight &gt;</a>'."\n";
			//$markup_string .= '<a href ="'.$this->passed_vars['link_to_full_item'].'">read spotlight &gt;</a>'."\n";
			$markup_string .= '</div class="desc">'."\n";
		}
		return $markup_string;
	}
	
	
	function get_date_markup()
	{
		$item = $this->passed_vars['item'];
		if($item->get_value( 'datetime') && $this->passed_vars['use_dates_in_list'] )
		{
			$datetime = prettify_mysql_datetime( $item->get_value( 'datetime' ), $this->passed_vars['date_format'] );
			return  '<div class="date">'.$datetime.'</div>'."\n";
		}
	}
	
	function get_title_markup()
	{
		$markup_string = '';
		$item = $this->passed_vars['item'];
		$link_to_full_item = $this->passed_vars['link_to_full_item'];
				
		$markup_string .=  '<h4>';
		if(isset($link_to_full_item) &&  !empty($link_to_full_item))
			$markup_string .=  '<a href="' .$link_to_full_item. '">'.$item->get_value('release_title').'</a>';
		else
			$markup_string .= $item->get_value('release_title');
		$markup_string .=  '</h4>'."\n";
		return $markup_string;
	}

}
?>
