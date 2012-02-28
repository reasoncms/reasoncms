<?php
	reason_include_once( 'minisite_templates/modules/blurb.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ContactBlurbModule';
	
	class ContactBlurbModule extends BlurbModule
	{
		function run()
		{
			$i = 0;
			$theme = get_theme($this->site_id);
			foreach( $this->blurbs as $blurb )
			{
				$i++;
	
				if (preg_match("/[Cc]ontact [Ii]nformation/", $blurb->get_value('name')))
				{
					echo '<section class="contact-information">'."\n";
					echo '<div class="contact-info">'."\n";
					if ($theme->get_value( 'name' ) != 'admissions')
					{
						echo '<h2>Contact Information</h2>'."\n";
					}
					echo $blurb->get_value('content');
					echo '</div>'."\n";
					echo '</section> <!-- class="contact-information" -->'."\n";
				}
			}
			// echo '</div>'."\n";
		}

		function has_content()
		{
			if(!empty($this->blurbs))
			{
				foreach($this->blurbs as $blurb)
				{
					if (preg_match("/[Cc]ontact [Ii]nformation/", $blurb->get_value('name')))
					{
						return true;
					}
				}
			
			}
			return false;
		} 
	}
?>
