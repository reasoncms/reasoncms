<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	reason_include_once( 'disco/plasmature/types/athletics.php' );
	reason_include_once( 'classes/sized_image.php' );
	reason_include_once('function_libraries/image_tools.php');
	//reason_include_once( 'classes/api/api.php' );
	//reason_include_once( 'minisite_templates/modules/image_sidebar.php' );
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
	
	var $site_name;
	var $positions;

	function init( $args = array() )
	{
		parent::init( $args );
		
		//Do standard initialization
		
		$head_items = $this->get_head_items();
		$head_items->add_javascript(JQUERY_URL, true);
		
		$head_items->add_javascript('/reason/local/luther_2014/javascripts/tablesorter.min.js');
		$head_items->add_javascript('/reason/local/luther_2014/javascripts/vendor/jquery.hoverIntent.min.js');
		$head_items->add_stylesheet('/reason/local/luther_2014/javascripts/vendor/jquery.cluetip.css');
		$head_items->add_javascript('/reason/local/luther_2014/javascripts/vendor/jquery.cluetip.min.js');		
		$head_items->add_javascript('/reason/local/luther_2014/javascripts/luther-sports-roster.js');
		$head_items->add_javascript('/reason/local/luther_2014/javascripts/luther-cluetip.js');
		
		if (defined(UNIVERSAL_CSS_PATH))
		{
			$head_items->add_stylesheet(UNIVERSAL_CSS_PATH);
		}
		
		$head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH.'FancyBox/source/jquery.fancybox.js');
		$head_items->add_stylesheet(REASON_PACKAGE_HTTP_BASE_PATH.'FancyBox/source/jquery.fancybox.css');
		$head_items->add_head_item('script', array('type'=>'text/javascript'),
			'$(document).ready(function() {
				$(".fancybox").fancybox({
					helpers		: {
					title	: { type : \'inside\' },
					}
				});
			});'
		);
		
		$site_id = new entity( $this->site_id );
		$this->site_name = $site_id->get_value('unique_name');
		
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
			if (($this->site_name == 'sport_baseball_men' || $this->site_name == 'sport_basketball_men' ||
				$this->site_name == 'sport_football_men' || $this->site_name == 'sport_soccer_men' ||
				$this->site_name == 'sport_basketball_women' || $this->site_name == 'sport_soccer_women' || 
				$this->site_name == 'sport_softball_women' || $this->site_name == 'sport_volleyball_women') &&
				$pv['athlete_number'] < 9999)
			{
				if ($pv['athlete_number'] == -2)
				{
					$anum = "01";
				}
				else if ($pv['athlete_number'] == -1)
				{
					$anum = "00";
				}
				else
				{
					$anum = $pv['athlete_number'];
				}
				$this->parent->title = "#" . $anum . " " . $pv['athlete_first_name'] . " " . $pv['athlete_last_name'];
			}
			else
			{
				$this->parent->title = $pv['athlete_first_name'] . " " . $pv['athlete_last_name'];
			}
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
		$this->sort_columns($this->_columns);
		$this->handle_abbreviated_position_events();
		
		if (!empty($this->request['id']))
		{
			echo '<div id="athleticsPlayerInfo">';
			$player = current($this->player_info); // get the player

			if (!empty($player['image_id']))
			{
				$image = get_entity_by_id($player['image_id']);
				$url = luther_get_image_url(WEB_PHOTOSTOCK . $player['image_id'] . '.' . $image['image_type']);
				$thumb = luther_get_image_url(WEB_PHOTOSTOCK . $player['image_id'] . '_tn.' . $image['image_type']);
				$orig = luther_get_image_url(WEB_PHOTOSTOCK . $player['image_id'] . '_orig.' . $image['image_type']);
				$description = $player['name'];
				if (file_exists($_SERVER['DOCUMENT_ROOT'] . $orig))   // link to high res original if it exists
				{
					$description .= '<a href="' . $orig . '" title="High res">&prop;</a>';
				}

				//$title = $player['name']<a href=\"http://farm" . $pinfo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $pinfo['originalsecret'] . "_o." . $pinfo['originalformat'] . "\" title=\"High res\">&prop;</a>\n";
				
				echo '<a class="fancybox" title="' . htmlspecialchars($description, ENT_COMPAT) . '" rel="group" href="' . $url .'">
					<img src="' . $thumb .'" alt="' . htmlspecialchars($description, ENT_COMPAT) . '" title="' . $player['name'] . '" /></a>';
				
				
					/*$rsi = new reasonSizedImage();
					if(!empty($rsi))
					{
						$rsi->set_id($image['id']);
						$rsi->set_width(300);
						//$rsi->set_height(600);
						$image = $rsi;
					}
				print_r($rsi);
				show_image( $rsi, false, true, false, "", false, false, "");
						
				echo "</div>\n";
				echo "</div>\n";
				
				$image = get_entity_by_id($player['image_id']);
				$url = luther_get_image_url(WEB_PHOTOSTOCK . $player['image_id'] . '.' . $image['image_type']);
				$thumb = luther_get_image_url(WEB_PHOTOSTOCK . $player['image_id'] . '_tn.' . $image['image_type']);
				$orig = luther_get_image_url(WEB_PHOTOSTOCK . $player['image_id'] . '_orig.' . $image['image_type']);
				$d = max($image['width'], $image['height']) / 125.0;
				$caption = $image['name'];
				if (file_exists($orig))   // link to high res original if it exists
				{
					$caption_hr = $caption . '<a href="' . $orig . '" title="High res">&prop;</a>';
				}
				else
				{
					$caption_hr = $caption;
				}
				echo '<div class="figure" style="width:' . intval($image['width']/$d) .'px;">';
				// show href to full size image with class and onclick for highslide
				echo '<a href="'. $url . '" class="highslide" onclick="return hs.expand(this, imageOptions)">';
				echo '<img src="' . $thumb . '" border="0" title="Click to enlarge" alt="' . htmlspecialchars($caption_hr, ENT_COMPAT) . '" />';
				echo '</a>';  

				// show caption if flag is true
				if ($caption != "") echo $caption;
				echo "</div>   <!-- class=\"figure\" -->\n";*/
			}
			
			echo '<ul class="no-bullet">';
			foreach ( array_keys($this->_columns) as $col)
			{
				
				if ($col != 'athlete_first_name' && $col != 'athlete_last_name' && $col != 'athlete_number' && $col != 'athlete_hometown_state')
				{
					$value = $player[$col];

					if ($col == 'athlete_position_event')
					{
						$value = $this->positions[$player['athlete_position_event']];
					}
					else if ($col == 'athlete_hometown_city')
					{
						$value .= ', '. $this->statesAP[$player['athlete_hometown_state']];
					}
					else if ($col == 'athlete_height')
					{
						if ($player[$col] > 0)
						{
							$value = (string)((int)($player[$col] / 12) . '\' ' . $player[$col] % 12 . '"');
						}
						else 
						{
							$value = "";
						}
					}
					else if ($col == 'athlete_weight')
					{
						if ($player[$col] > 0)
						{
							$value = $player[$col];
						}
						else 
						{
							$value = "";
						}
					}

				echo '<li>'.$this->gen_custom_header($col).': '.$value.'</li>';
				}
			}
			echo '</ul>';
			echo '<hr>';
			if (!empty($player['content'])) {	
				echo $player['content']."\n";
			}
			echo '</div>', "\n";
			//echo '</div>';
		}
		else
		{
			$str = '';
			$show_captain = false;
			$show_letter = false;

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
				$ct = "";   // appended cluetip information

				foreach( $this->player_info as $k => $player )
				{
						$row = 1 - $row;
						$str .= '<tr>';
						foreach( array_keys($this->_columns) as $col )
						{
							$str .= '<td>';
							if ($col == 'athlete_number')
							{
								if ($player[$col] == -2)
								{
									$str .= "01";
								}
								else if ($player[$col] == -1)
								{
									$str .= "00";
								}
								else if ($player[$col] < 9999)
								{
									$str .= $player[$col];
								}
							}
							else if ($col == 'athlete_first_name' || $col == 'athlete_last_name')
							{
								$name = $player[$col]; //.' '.$player['athlete_last_name'];
								$player_link = carl_make_link(array('id' => $k));
								//$str .= '<a href="'.$player_link.'">'.$name.'</a>';
								$str .= "<a href=\"".$player_link. "\" class=\"cluetip_athlete\" title=\"". $player['athlete_first_name']." ".$player['athlete_last_name'] ."\" rel=\"#athlete".$player['id']."\">".$name."</a>";
								
								if ($col == 'athlete_last_name' && $player['athlete_letter'] == 'yes'
									&& $col == 'athlete_last_name' && $player['athlete_captain'] == 'yes')
								{
									$str .= "&nbsp;&#x25b5;&#x25a1;";
									$show_letter = true;
									$show_captain = true;
								}
								else if ($col == 'athlete_last_name' && $player['athlete_letter'] == 'yes')
								{
									$str .= "&nbsp;&#x25b5;";
									$show_letter = true;
								}
								else if ($col == 'athlete_last_name' && $player['athlete_captain'] == 'yes')
								{
									$str .= "&nbsp;&#x25a1;";
									$show_captain = true;
								}
							}
							else if ($col == 'athlete_class_year')
							{
								$str .= preg_replace("|^Fy$|", "Fr", $this->class_year[$player[$col]]);
								//$str .= $this->class_year[$player[$col]];
								
							}
							else if ($col == 'athlete_height')
							{
								//$str .= (string)((int)($player[$col] / 12) . '\' ' . str_pad($player[$col] % 12, 2, '0', STR_PAD_LEFT) . '"');
								if ($player[$col] > 0)
								{
									$str .= (string)((int)($player[$col] / 12) . '\' ' . $player[$col] % 12 . '"');
								}
							}
							else if ($col == 'athlete_weight')
							{
								if ($player[$col] > 0)
								{
									$str .= $player[$col];
								}
							}
							else if ($col == 'athlete_hometown_state')
							{
								if (array_key_exists($player[$col], $this->statesAP))
								{
									$str .= $this->statesAP[$player[$col]];
								}
							}
							else
							{
								$str .= $player[$col];
							}
							$str .= '</td>';
						}
						$str .= '</tr>';
						
						$ct .= "<div id=\"athlete".$player['id']."\">";
						$ct .= "<p class=\"athlete_position_event\">". $this->positions[$player['athlete_position_event']];
						if (!empty($player['image_id']))
						{
							$image = get_entity_by_id($player['image_id']);
							$thumb = luther_get_image_url(WEB_PHOTOSTOCK . $player['image_id'] . '_tn.' . $image['image_type']);
							$ct .= "<img class=\"athlete_image\" src=\"" . $thumb . "\" />";
						}
						$ct .= "</p>";					
						$ct .= "<p class=\"athlete_class_year\">". $player['athlete_class_year']."</p>";
						$ct .= "<p class=\"athlete_hometown\">". $player['athlete_hometown_city'];
						if (array_key_exists($player['athlete_hometown_state'], $this->statesAP))
						{
							$ct .= ", ". $this->statesAP[$player['athlete_hometown_state']];
						}
						$ct .= "</p>";
						$ct .= "<p class=\"athlete_high_school\">". $player['athlete_high_school']."</p>";		
						$ct .= "</div>";

				}

				$str .= '</tbody></table>';
				if ($show_letter && $show_captain)
				{
					$str .= '<p>&#x25b5;&nbsp;letter winner<br/>';
					$str .= '&#x25a1;&nbsp;captain</p>';
				}
				else if ($show_letter)
				{
					$str .= '<p>&#x25b5;&nbsp;letter winner</p>';
				}
				else if ($show_captain)
				{
					$str .= '<p>&#x25a1;&nbsp;captain</p>';
				}

			echo $str . $ct;
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
				if (empty($this->request['id']))
				{
					return 'Pos.';
				}
				else 
				{
					return 'Position';
				}
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
			if (empty($this->request['id']))
			{
				return 'Ht.';
			}
			else 
			{
				return 'Height';
			}
		}
		else if ($k == 'athlete_weight')
		{
			if (empty($this->request['id']))
			{
				return 'Wt.';
			}
			else 
			{
				return 'Weight';
			}
		}
		else if ($k == 'athlete_bat')
		{
			if (empty($this->request['id']))
			{
				return 'B';
			}
			else 
			{
				return 'Bat';
			}
		}
		else if ($k == 'athlete_throw')
		{
			if (empty($this->request['id']))
			{
				return 'T';
			}
			else 
			{
				return 'Throw';
			}
		}
		return $this->table_header[$k];

	}
	
	function sort_columns(&$columns)
	{
		$sort_order = array('athlete_number' => 0, 'athlete_first_name' => 1,
			'athlete_last_name' => 2, 'athlete_position_event' => 3, 'athlete_bat' => 4,
			'athlete_throw' => 5, 'athlete_height' => 6, 'athlete_weight' => 7,
			'athlete_class_year' => 8, 'athlete_hometown_city' => 9,
			'athlete_hometown_state' => 10, 'athlete_high_school' => 11); // specify whatever you want sorted in this manner
		$key_count = count($sort_order);
		foreach ($columns as $k=>$v)
		{
			if (isset($sort_order[$k]))
			{
				$new_order[$sort_order[$k]] = $k;
			}
			else
			{
				$new_order[$key_count] = $k;
				$key_count++;
			}
		}
		ksort($new_order);
		$new_order = array_flip($new_order);
		foreach($columns as $k=>$v)
		{
			$new_order[$k] = $columns[$k];
		}
		$columns = $new_order;
	}
	
	function handle_abbreviated_position_events()
	// replaces abbreviated position or event with the full name
	{
		if ($this->site_name == 'sport_baseball_men' || $this->site_name == 'sport_softball_women')
		{
			$this->positions = array(
				'P' => 'Pitcher',
				'C' => 'Catcher',
				'IF' => 'Infield',
				'1B' => 'First Base',
				'2B' => 'Second Base',
				'3B' => 'Third Base',
				'SS' => 'Shortstop',
				'OF' => 'Outfield',
			);
		}
		else if ($this->site_name == 'sport_basketball_men' || $this->site_name == 'sport_basketball_women')
		{
			$this->positions = array(
				'C' => 'Center',
				'F' => 'Forward',
				'G' => 'Guard',
			);
		}
		else if ($this->site_name == 'sport_football_men' )
		{
			$this->positions = array(
				'DB' => 'Defensive Back',
				'DL' => 'Defensive Line',
				'FB' => 'Fullback',
				'K' => 'Kicker',
				'LB' => 'Linebacker',
				'OL' => 'Offensive Line',
				'QB' => 'Quarterback',
				'RB' => 'Running Back',
				'TE' => 'Tight End',
				'WR' => 'Wide Receiver',
			);
		}
		else if ($this->site_name == 'sport_soccer_men' || $this->site_name == 'sport_soccer_women')
		{
			$this->positions = array(
				'GK' => 'Goalkeeper',
				'D' => 'Defender',
				'MF' => 'Midfielder',
				'F' => 'Forward',
			);
		}
		else if ($this->site_name == 'sport_volleyball_women' )
		{
			$this->positions = array(
				'DS' => 'Defensive Specialist',
				'L' => 'Libero',
				'MB' => 'Middle Blocker',
				'OH' => 'Outside Hitter',
				'R' => 'Right Side Hitter',
				'S' => 'Setter',
			);
		}
		else if ($this->site_name == 'sport_swimmingdiving_men' || $this->site_name == 'sport_swimmingdiving_women')
		{
			$this->positions = array(
				'BU' => 'Butterfly',
				'BA' => 'Backstroke',
				'BR' => 'Breaststroke',
				'FR' => 'Freestyle',
				'IM' => 'Individual Medley',
				'D' => 'Diving',
			);
		}
		else if ($this->site_name == 'sport_trackfield_men' || $this->site_name == 'sport_trackfield_women')
		{
			$this->positions = array(
				'D' => 'Distance',
				'H' => 'Hurdles',
				'J' => 'Jumps',
				'MD' => 'Mid-distance',
				'ME' => 'Multi-events',
				'PV' => 'Pole Vault',
				'S' => 'Sprints',
				'T' => 'Throws',
			);
		}
	}
	
}
?>
