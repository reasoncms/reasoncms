<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	//reason_include_once( 'classes/error_handler.php');

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'lutherSportsRosterModule';

	class lutherSportsRosterModule extends DefaultMinisiteModule
	{
			/**
	 * sport entity associated with minisite page
	 * @var object sport
	 */
	var $sport;

	/**
	 * full set of player data indexed by [player_id][column_name] - built while determing valid columns - also stores
	 * whether or not there is an associated image with [player_id]['has_image'].
	 * @var array players_info
	 */
	var $player_info = array();

	var $no_show = array(
            'type' => true,
            'last_edited_by' => true,
            'last_name' => true,
            'id' => true,
            'last_modified' => true,
            'content' => true,
            'name' => true,
            'author' => true,
            'creation_date' => true,
            'state' => true,
            'created_by' => true,
            'image_id' => true,
			'sort_order' => true,
			'athlete_hide' => true,
			'athlete_gender' => true,
			'athlete_letter' => true,
			'athlete_captain' => true,
        );

	var $no_show_detail = array(
            'type' => true,
            'last_edited_by' => true,
            'last_name' => true,
            'id' => true,
            'last_modified' => true,
            'content' => true,
            'name' => true,
            'author' => true,
            'creation_date' => true,
            'state' => true,
            'created_by' => true,
            'image_id' => true,
			'sort_order' => true,
			'athlete_hide' => true,
			'athlete_gender' => true,
			'athlete_letter' => true,
			'athlete_captain' => true,
        );
        
	var $table_header = array(
			'athlete_first_name' => 'First',
			'athlete_last_name' => 'Last',
			'athlete_number' => '#&nbsp;',
			'athlete_hometown_city' => 'Hometown',
			'athlete_hometown_state' => 'State',
			'athlete_high_school' => 'High School',
			'athlete_class_year' => 'Year',
		);
		
	var $class_year = array(
			'First Year' => 'Fy',
			'Sophomore' => 'So',
			'Junior' => 'Jr',
			'Senior' => 'Sr',
			'Grad' => 'Gr',
		);
	var $bat = array(
			'left' => 'L',
			'right' => 'R',
			'switch' => 'S',
		);
	var $throw = array(
			'left' => 'L',
			'right' => 'R',
		);
	var $statesAP = array(
                       'AL' => 'Ala.',
                       'AK' => 'Alaska',
                       'AZ' => 'Ariz.',
                       'AR' => 'Ark.',
                       'CA' => 'Calif.',
                       'CO' => 'Colo.',
                       'CT' => 'Conn.',
                       'DE' => 'Del.',
                       'DC' => 'D.C.',
                       'FL' => 'Fla.',
                       'GA' => 'Ga.',
                       'HI' => 'Hawaii',
                       'ID' => 'Idaho',
                       'IL' => 'Ill.',
                       'IN' => 'Ind.',
                       'IA' => 'Iowa',
                       'KS' => 'Kan.',
                       'KY' => 'Ky.',
                       'LA' => 'La.',
                       'ME' => 'Maine',
                       'MD' => 'Md.',
                       'MA' => 'Mass.',
                       'MI' => 'Mich.',
                       'MN' => 'Minn.',
                       'MS' => 'Miss.',
                       'MO' => 'Mo.',
                       'MT' => 'Mont.',
                       'NE' => 'Neb.',
                       'NV' => 'Nev.',
                       'NH' => 'N.H.',
                       'NJ' => 'N.J.',
                       'NM' => ' N.M.',
                       'NY' => 'N.Y.',
                       'NC' => 'N.C.',
                       'ND' => ' N.D.',
                       'OH' => 'Ohio',
                       'OK' => ' Okla.',
                       'OR' => 'Ore.',
                       'PA' => 'Pa.',
                       'RI' => 'R.I.',
                       'SC' => 'S.C.',
                       'SD' => 'S.D.',
                       'TN' => 'Tenn.',
                       'TX' => 'Texas',
                       'UT' => 'Utah',
                       'VT' => 'Vt.',
                       'VA' => 'Va.',
                       'WA' => 'Wash.',
                       'WV' => 'W.Va.',
                       'WI' => 'Wis.',
                       'WY' => 'Wyo.',
               );

	var $_columns = array();

	var $cleanup_rules = array(
            'id' => array('function' => 'turn_into_int'),
            'player_sort' => array(
                'function' => 'check_against_array', 'extra_args' => array(
                    'name_sort_asc',
                    'name_sort_desc',
                    'number_sort_asc',
                    'number_sort_desc',
                    'year_sort_asc',
                    'year_sort_desc')));

	function init( $args = array() )
	{
		$es = new entity_selector($this->site_id);
		$es->add_type(id_of('athlete_type'));
		if (!empty($this->request['id']))   // individual
		{
			$es->add_relation('entity.id = ' . $this->request['id']);
		}
		else   // roster
		{
			$es->add_relation('athlete_hide != "yes"');
			$es->set_order('athlete_number, athlete_last_name, athlete_first_name'); // omit table name due to union query
		}
		
		$es->add_left_relationship_field( 'athlete_to_image', 'entity', 'id', 'image_id', false); // get images and those with no image - uses union query

		$players = $es->run_one();

		if (!empty($players))
		{
			foreach ($players as $k=>$v)
			{
				$pv = $v->get_values();
				$this->player_info[$k] = $pv;
				foreach ($pv as $k2=>$v2)
				{
					$display = (!empty($this->request['player_id'])) ? empty($this->no_show_detail[$k2]) : empty($this->no_show[$k2]);
					if (!empty($v2) && $display)
					{
						$this->_columns[$k2] = true;
					}
				}
			}
		}
		if (!empty($players) && !empty($this->request['id'])) // add crumb with player name and alter title - pv will have the values we need
		{
			$this->_add_crumb($pv['athlete_first_name'] . " " . $pv['athlete_last_name']);
			$this->parent->title = $pv['athlete_first_name'] . " " . $pv['athlete_last_name'];
		}
		//$this->init_player_order();
	}

	function has_content()
	{
		if (!empty($this->player_info)) return true;
		elseif (!empty($this->request['id']))
		{
			echo '<p><strong>No information is available for the player you requested.</strong></p>';
			return false;
		}
		else
		{
			echo '<p><strong>There are currently no players on the roster for this sport.</strong></p>';
			return false;
		}
	}

	function run()
	{
		if (!empty($this->request['id']))
		{
			$player_class = 'playerInfo';
			echo '<div id="athleticsPlayerInfo">';
			$player = current($this->player_info); // get the player

			if (!empty($player['image_id']))
			{
				echo '<div class="playerImage">';
				show_image($player['image_id']);
				echo '</div>';
				$player_class = 'playerInfoWithImage'; // apply indentation to text since an image was shown
			}
			echo '<div class="'.$player_class.'">';
			echo '<h3>'.$player['first_name'] ." ". $player['last_name'].'</h3>';
			unset ($this->_columns['first_name']);
			echo '<ul>';
			foreach ( array_keys($this->_columns) as $col)
			{
				$value = $player[$col];
				if ($col == 'bats' || $col == 'throws') {
					switch ($value) {
						case 'L':
							$value = 'Left';
							break;
						case 'R':
							$value = 'Right';
							break;
					}
				}
				echo '<li><strong>'.prettify_string($col).':</strong> '.$value.'</li>';
			}
			echo '</ul>';
			if (!empty($player['content'])) {
				echo '<h4>Additional Information</h4>'."\n";
				echo '<div class="moreInfo">'."\n";
				echo $player['content']."\n";
				echo '</div>'."\n";
			}
			echo '</div>', "\n";
			echo '<p class="returnLink"><a href="'.carl_make_link(array('id'=>'')).'">View full roster</a></p>';
			echo '</div>';
		}
		else
		{
			$str = '';

				// now display the roster
				$str .= '<table class="tablesorter"><thead><tr>';

				foreach ($this->_columns as $k => $v)
				{
					// allows custom table sorting parser to be used for a given column
					// the sorting parser is located in javascripts/jquery.init.js
					// see http://tablesorter.com/docs/example-meta-headers.html
					$str .= '<th class="{sorter: \'' . $k .'\'}">';   
					$str .= $this->gen_custom_header($k);
					$str .= '</th>';
				}
				$str .= '</tr></thead><tbody>';

				$row = 1;

				foreach( $this->player_info as $k => $player )
				{
						$row = 1 - $row;
						$str .= '<tr>';
						foreach( array_keys($this->_columns) as $col )
						{
							$str .= '<td>';
							if($col == 'athlete_first_name' || $col == 'athlete_last_name')
							{
								$name = $player[$col]; //.' '.$player['athlete_last_name'];
								if ( $player['image_id'] OR $player['content'] )
								{
									$player_link = carl_make_link(array('id' => $k));
									$str .= '<a href="'.$player_link.'">'.$name.'</a>';
								}
								else
								{
									$str .= $name;
								}
								if ($col == 'athlete_last_name' && $player['athlete_letter'] == 'yes')
								{
									$str .= "&bull;";
								}
								if ($col == 'athlete_last_name' && $player['athlete_captain'] == 'yes')
								{
									$str .= "&diams;";
								}
							}
							else if ($col == 'athlete_class_year')
							{
								$str .= $this->class_year[$player[$col]];
							}
							else if ($col == 'athlete_height')
							{
								//$str .= (string)((int)($player[$col] / 12) . '\' ' . str_pad($player[$col] % 12, 2, '0', STR_PAD_LEFT) . '"');
								$str .= (string)((int)($player[$col] / 12) . '\' ' . $player[$col] % 12 . '"');
							}
							else if ($col == 'athlete_hometown_state' && $this->statesAP[$player[$col]] != '')
							{
								$str .= $this->statesAP[$player[$col]];
							}
							else
							{
								$str .= $player[$col];
							}
							$str .= '</td>';
						}
						$str .= '</tr>';

				}

				$str .= '</tbody></table>';

			echo $str;
		}
	}
	function gen_custom_header($k)
	{
		if ($k == 'athlete_position_event')
		{
			if ($this->site_id == id_of('sport_baseball_men') || $this->site_id == id_of('sport_football_men') ||
				$this->site_id == id_of('sport_basketball_men') || $this->site_id == id_of('sport_basketball_women') ||
				$this->site_id == id_of('sport_soccer_men') || $this->site_id == id_of('sport_soccer_women') ||
				$this->site_id == id_of('sport_softball_women') || $this->site_id == id_of('sport_volleyball_women'))
			{
				return 'Pos.';
			}
			else if ($this->site_id == id_of('sport_swimmingdiving_men') || $this->site_id == id_of('sport_swimmingdiving_women') ||
				$this->site_id == id_of('sport_track_men') || $this->site_id == id_of('sport_track_women'))
			{
				return 'Event';
			}
			else
			{
				return '';
			}
		}
		else if ($k == 'athlete_height')
		{
			return 'Ht.';
		}
		else if ($k == 'athlete_weight')
		{
			return 'Wt.';
		}
		else if ($k == 'athlete_bat')
		{
			return 'B';
		}
		else if ($k == 'athlete_throw')
		{
			return 'T';
		}
		return $this->table_header[$k];

	}

}
?>
