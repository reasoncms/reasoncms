<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'Athlete';

	/**
	 * A content manager for text blurbs
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

			if ($site_name == 'sport_baseball_men' || $site_name == 'sport_basketball_men' ||
				$site_name == 'sport_football_men' || $site_name == 'sport_soccer_men' ||
				$site_name == 'sport_basketball_women' || $site_name == 'sport_soccer_women' || 
				$site_name == 'sport_softball_women' || $site_name == 'sport_volleyball_women')
			{
				$this->set_display_name('athlete_number', 'Number');
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

			$this->set_display_name('athlete_hometown', 'Hometown');
			$this->set_comments('athlete_hometown', form_comment('Enter City, State (Use AP abbreviation for state. E.g. Iowa, Minn., Wis.)'));
			$this->set_display_name('athlete_high_school', 'High School');
			$this->add_element('athlete_letter', 'checkbox', array('display_name' => 'Letter', 'description' => 'check if letter winner'));
			$this->add_element('athlete_captain', 'checkbox', array('display_name' => 'Captain', 'description' => 'check if team captain'));
			$this->add_element('athlete_hide', 'checkbox', array('display_name' => 'Hide', 'description' => 'check if suspended or graduated'));

			if ($site_name == 'sport_baseball_men' || $site_name == 'sport_basketball_men' ||
				$site_name == 'sport_football_men' || $site_name == 'sport_soccer_men' ||
				$site_name == 'sport_basketball_women' || $site_name == 'sport_soccer_women' || 
				$site_name == 'sport_softball_women' || $site_name == 'sport_volleyball_women')
			{
				$this->set_display_name('athlete_position_event', 'Position');
			}
			else if ($site_name == 'sport_swimming_men' || $site_name == 'sport_track_men' ||
				$site_name == 'sport_swimming_women' || $site_name == 'sport_swimming_women') 
			{
				$this->set_display_name('athlete_position_event', 'Event');
			}
			else
			{
				$this->change_element_type('athlete_position_event', 'hidden');
			}
				
			if ($site_name == 'sport_basketball_men' || $site_name == 'sport_football_men' ||
				$site_name == 'sport_basketball_women' || $site_name == 'sport_volleyball_women')
			{
				$this->set_display_name('athlete_height', 'Height');
			}
			else
			{
				$this->change_element_type('athlete_height', 'hidden');
			}

			if ($site_name == 'sport_football_men' || $site_name == 'sport_wrestling_men')
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
			
			$this->set_order(
				array(
					'name',
					'athlete_number',
					'athlete_first_name',
					'athlete_last_name',
					'athlete_gender',
				)
			);

		}
	}
?>
