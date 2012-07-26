<?php
	reason_include_once( 'minisite_templates/modules/image_sidebar.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherBanneradModule';
	
	class LutherBanneradModule extends ImageSidebarModule
	{
		var $es;
		var $images;

		var $acceptable_params = array(
		'num_to_display' => '',
		'caption_flag' => true,
		'rand_flag' => false,
		'order_by' => '' );

		function init( $args = array() )
		{
			parent::init( $args );
			$head_items =& $this->parent->head_items;
		}
		
		function run()
		{
			$die = isset( $this->die_without_thumbmail ) ? $this->die_without_thumbnail : false;
			$popup = isset( $this->show_popup_link ) ? $this->show_popup_link : true;
			$desc = isset( $this->description ) ? $this->description : true;
			$text = isset( $this->additional_text ) ? $this->additional_text : "";
			if ( !empty($this->parent->textonly) )
				echo '<p>Banner ads</p>'."\n";
			
			$theme = get_theme($this->site_id);
			if ($theme->get_value( 'name' ) == 'luther2010')
			{
				echo '<aside class="banners">'."\n";
				echo '<ul>'."\n";
			}
			else
			{
				echo '<div id="bannerleft">'."\n";
			}

			$i = 0;
			foreach( $this->images AS $id => $image )
			{
				if (preg_match("/bannerad\s(.*?)$/", $image->get_value('keywords'), $matches))
				{
					$url = WEB_PHOTOSTOCK . $id . '.' . $image->get_value('image_type');
					if ($theme->get_value( 'name' ) == 'luther2010')
					{
						echo '<li>'."\n";
						echo '<a href="' . $matches[1] . '"><img src="' . $url . '" alt="' . $image->get_value('description') . '" width="235" height="90"/></a>';
						echo '</li>'."\n";
					}
					else
					{
						echo '<a href="' . $matches[1] . '"><img src="' . $url . '" alt="' . $image->get_value('description') . '" width="100%" /></a>';
					}
					$i++;
				}
				if ($theme->get_value( 'name' ) == 'luther2010' && $this->cur_page->get_value( 'custom_page' ) == 'luther2010_home' && $i >= 4)
				{
					break;
				} 
			}
			
			if ($theme->get_value( 'name' ) == 'luther2010')
			{
				echo '</ul>'."\n";
				echo '</aside> <!--class="banners"-->'."\n";
			}
			else
			{
				echo '</div> <!--id="bannerleft"-->'."\n";
			}
			//echo "</div>\n";
		}
	}
?>
