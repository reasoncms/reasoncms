<?php
	reason_include_once( 'minisite_templates/modules/blurb.php' );
	include_once( CARL_UTIL_INC . 'dir_service/directory.php' );

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
					$s = str_replace(array("\r", "\r\n", "\n"), '', $blurb->get_value('content'));						
					if (preg_match("/(.*?)([Ll][Dd][Aa][Pp]:\s?)([a-z\d]+)(.*)/", $s, $m ))
					{
						echo $m[1] . $this->process_ldap($m[3]) . $m[4] . "\n";
					}
					else
					{
						echo $s;
					}
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

		function process_ldap($username)
		{
			$required_attributes = array('ds_email','ds_fullname','ds_lastname','ds_affiliation','ds_phone', 'ds_office', 'ds_title', 'ds_gecos', 'ds_cn');			
			$dir = new directory_service();
			
			$filter = '(ds_username='.$username.')';
			if ($dir->search_by_filter($filter, $required_attributes))
			{
				$person =  $dir->get_first_record();
				echo '<p>'.$person['ds_fullname'][0];
				if (!empty($person['ds_title']))
				{
					echo '<br />'.$person['ds_title'][0];
				}
				echo '</p>'."\n";
				
				if (!empty($person['ds_office']))
				{
					echo '<h3>Office:</h3>'."\n";
					echo '<p>'.$person['ds_office'][0].'</p>'."\n";
				}
				
				if (!empty($person['ds_phone']))
				{
					echo '<h3>Phone:</h3>'."\n";
					echo '<p>'.$person['ds_phone'][0].'</p>'."\n";
				}
				
				if (!empty($person['ds_email']))
				{
					echo '<h3>Email:</h3>'."\n";
					echo '<p><a href="mailto:'.$person['ds_email'][0].'">'.$person['ds_email'][0].'</a></p>'."\n";
				}
			}	
		}
		
	}
?>
