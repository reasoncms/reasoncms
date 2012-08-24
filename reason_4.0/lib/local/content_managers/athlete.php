<?php
reason_include_once( 'disco/plasmature/types/athletics.php' );

/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'Athlete';

	/**
	 * A content manager for Luther College athletes and sports rosters
	 */
	class Athlete extends ContentManager
	{
		function alter_data()
		{
			$site_id = new entity( $this->get_value( 'site_id' ) );
			$site_name = $site_id->get_value('unique_name');

			$this->set_comments('name', form_comment('A name for internal reference. E.g. the athlete\'s full name'));
			$this->change_element_type('unique_name', 'hidden');
			$this->change_element_type('author', 'hidden');
			$this->change_element_type('no_share', 'hidden');

			if ($site_name == 'sport_baseball_men' || $site_name == 'sport_basketball_men' ||
				$site_name == 'sport_football_men' || $site_name == 'sport_soccer_men' ||
				$site_name == 'sport_basketball_women' || $site_name == 'sport_soccer_women' || 
				$site_name == 'sport_softball_women' || $site_name == 'sport_volleyball_women')
			{
				$this->set_display_name('athlete_number', 'Number');
				if ($this->get_value('athlete_number') >= 9999)
				{
					$this->set_value('athlete_number', null);
				}
			}
			else
			{
				$this->change_element_type('athlete_number', 'hidden');
			}


			$this->set_display_name('athlete_first_name', 'First Name');
			$this->set_display_name('athlete_last_name', 'Last Name');

			$this->set_display_name('athlete_gender', 'Gender');
			if (!$this->get_value('athlete_gender') && preg_match("/women/", $site_name))
			{
				$this->set_value('athlete_gender', 'Female');
			}
			else if (!$this->get_value('athlete_gender') && preg_match("/men/", $site_name))
			{
				$this->set_value('athlete_gender', 'Male');
			}

			$this->set_display_name('athlete_class_year', 'Class Year');

			$this->set_display_name('athlete_hometown_city', 'Hometown City');
			$st = $this->get_value('athlete_hometown_state');
			$this->add_element('athlete_hometown_state', 'state_province', array('display_name' => 'Hometown State')); 
			$this->set_value('athlete_hometown_state', $st);
			//$this->set_comments('athlete_hometown', form_comment('Enter City, State (Use AP abbreviation for state. E.g. Iowa, Minn., Wis.)'));
			$this->set_display_name('athlete_high_school', 'High School');
			$this->set_display_name('athlete_letter', 'Letter');
			$this->set_display_name('athlete_captain', 'Captain');
			$this->set_display_name('athlete_hide', 'Hide');

			if ($site_name == 'sport_baseball_men' || $site_name == 'sport_basketball_men' ||
				$site_name == 'sport_football_men' || $site_name == 'sport_soccer_men' ||
				$site_name == 'sport_basketball_women' || $site_name == 'sport_soccer_women' || 
				$site_name == 'sport_softball_women' || $site_name == 'sport_volleyball_women' ||
				$site_name == 'sport_swimmingdiving_men' || $site_name == 'sport_swimmingdiving_women' ||
				$site_name == 'sport_trackfield_men' || $site_name == 'sport_trackfield_women')
			{
				$pe = $this->get_value('athlete_position_event');
				if ($site_name == 'sport_baseball_men' || $site_name == 'sport_softball_women')
				{
					$this->add_element('athlete_position_event', 'baseball_softball_positions', array('display_name' => 'Position'));
				}
				else if ($site_name == 'sport_basketball_men' || $site_name == 'sport_basketball_women')
				{
					$this->add_element('athlete_position_event', 'basketball_positions', array('display_name' => 'Position'));
				}
				else if ($site_name == 'sport_football_men')
				{
					$this->add_element('athlete_position_event', 'football_positions', array('display_name' => 'Position'));
				}
				else if ($site_name == 'sport_soccer_men' || $site_name == 'sport_soccer_women')
				{
					$this->add_element('athlete_position_event', 'soccer_positions', array('display_name' => 'Position'));
				}
				else if ($site_name == 'sport_volleyball_women')
				{
					$this->add_element('athlete_position_event', 'volleyball_positions', array('display_name' => 'Position'));
				}
				else if ($site_name == 'sport_swimmingdiving_men' || $site_name == 'sport_swimmingdiving_women')
				{
					$this->add_element('athlete_position_event', 'swimming_events', array('display_name' => 'Event'));
				}
				else if ($site_name == 'sport_trackfield_men' || $site_name == 'sport_trackfield_women')
				{
					$this->add_element('athlete_position_event', 'track_events', array('display_name' => 'Event'));
				}
				$this->set_value('athlete_position_event', $pe);
			}
			else
			{
				$this->change_element_type('athlete_position_event', 'hidden');
			}
				
			if ($site_name == 'sport_basketball_men' || $site_name == 'sport_football_men' ||
				$site_name == 'sport_basketball_women' || $site_name == 'sport_volleyball_women' ||
				$site_name == 'sport_soccer_men' || $site_name == 'sport_soccer_women')
			{
				$this->change_element_type('athlete_height', 'hidden');
				$h = $this->get_value('athlete_height');
				$this->add_element('athlete_height_text', 'text', array('display_name' => 'Height'));
				if ($h > 0)
				{
					$this->set_value('athlete_height_text', (string)((int)($h / 12) . ' ' . $h % 12));
				}
				$this->set_comments('athlete_height_text', form_comment('In feet and inches. (e.g. 5 10, 6 0, 6 7)'));
				//$this->set_display_name('athlete_height', 'Height');
			}
			else
			{
				$this->change_element_type('athlete_height', 'hidden');
			}

			if ($site_name == 'sport_football_men' || $site_name == 'sport_wrestling_men' ||
				$site_name == 'sport_soccer_men')
			{
				$this->set_display_name('athlete_weight', 'Weight');
			}
			else
			{
				$this->change_element_type('athlete_weight', 'hidden');
			}

			if ($site_name == 'sport_baseball_men' || $site_name == 'sport_softball_women')
			{
				$this->set_display_name('athlete_bat', 'Bat');
				$this->set_display_name('athlete_throw', 'Throw');
			}
			else
			{
				$this->change_element_type('athlete_bat', 'hidden');
				$this->change_element_type('athlete_throw', 'hidden');
			}

			$this->change_element_type('athlete_extra', 'hidden');

			$this->add_required('athlete_first_name');
			$this->add_required('athlete_last_name');

			// lokify the content box
			$this->change_element_type('content' , html_editor_name($this->admin_page->site_id), html_editor_params($this->admin_page->site_id, $this->admin_page->user_id));
			
			$this->set_order(
				array(
					'name',
					'athlete_number',
					'athlete_first_name',
					'athlete_last_name',
					'athlete_gender',
					'athlete_class_year',
					'athlete_hometown_city',
					'athlete_hometown_state',
					'athlete_high_school',
					'athlete_letter',
					'athlete_captain',
					'athlete_hide',
					'athlete_position_event',
					'athlete_height_text',
					'athlete_weight',
					'athlete_bat',
					'athlete_throw',
					'athlete_content',
				)
			);

		}

		function process()
		{
			$site_id = new entity( $this->get_value( 'site_id' ) );
			$site_name = $site_id->get_value('unique_name');
			
			// convert text field for height in feet and inches to an integer value in inches
			if (preg_match("/(\d+)[\s\"',\-fet\.]*(\d*)/", $this->get_value('athlete_height_text'), $matches))
			{
				if ($matches[2] == '' && (int)$matches[1] > 10)
				{
					$this->set_value('athlete_height', (int)$matches[1]);
				}
				else if (sizeof($matches) == 3)
				{
					$this->set_value('athlete_height', (int)$matches[1] * 12 + (int)$matches[2]);
				}
			}
			if (($site_name == 'sport_baseball_men' || $site_name == 'sport_basketball_men' ||
				$site_name == 'sport_football_men' || $site_name == 'sport_soccer_men' ||
				$site_name == 'sport_basketball_women' || $site_name == 'sport_soccer_women' || 
				$site_name == 'sport_softball_women' || $site_name == 'sport_volleyball_women') &&
				$this->get_value('athlete_number') == null)
			{
				$this->set_value('athlete_number', 9999);
			}
			parent::process();
		}
	}
?>
