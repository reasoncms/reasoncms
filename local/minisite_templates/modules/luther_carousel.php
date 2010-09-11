<?php
	reason_include_once( 'minisite_templates/modules/image_sidebar.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherCarouselModule';
	
	class LutherCarouselModule extends ImageSidebarModule
	{
		var $es;
		var $images;

		var $acceptable_params = array(
		'num_to_display' => '',
		'caption_flag' => true,
		'rand_flag' => true,
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
				echo '<h3>Image Carousel</h3>'."\n";
			
			echo '<aside id="carousel">'."\n";
			echo '<ol id="slides">'."\n";
				
			$i = 1;
			foreach( $this->images AS $id => $image )
			{
				if (preg_match("/imagetop/", $image->get_value('keywords')))
				{
					$url = WEB_PHOTOSTOCK . $id . '.' . $image->get_value('image_type');
					if ($i <= 6)
					{
						if ($i == 1)
						{
							echo '<li class="active slide open" id="slide-'.$i.'">';
						}
						else
						{
							echo '<li class="slide open" id="slide-'.$i.'">';						
						}
						echo '<img src="' . $url . '" alt="" height="288" width="475" /><span></span></li>'."\n";
					}
					else
					{
						if ($i == 7)
						{
							echo '<script type="text/javascript">'."\n";
							echo 'var images = ['."\n";
						}
						echo '{ src: \'' . $url . '\' },'."\n";
					}
					$i++;
				}
				
			}
			if ($i > 6)
			{
				echo ']'."\n".'</script>'."\n";
			}
			echo '</ol>'."\n";
			echo '</aside>'."\n";
		}
	}
?>
