<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	//reason_include_once( 'classes/error_handler.php');

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'sportsRosterTestModule';

	class sportsRosterTestModule extends DefaultMinisiteModule
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

	/**
	 * @var array _gender
	 */
	var $gender = array( 1 => 'M', 2 => 'F' );

	/**
	 * @var array no_show
	 */
	var $no_show = array(
            'type' => true,
            'last_edited_by' => true,
            'last_name' => true,
            'gender' => true,
            'major' => true,
            'id' => true,
            'last_modified' => true,
            'content' => true,
            'name' => true,
            'author' => true,
            'creation_date' => true,
            'state' => true,
            'created_by' => true,
            'image_id' => true,
        );

		/**

	 * @var array no_show
	 */
	var $no_show_detail = array(
            'type' => true,
            'last_edited_by' => true,
            'last_name' => true,
            'gender' => true,
            'id' => true,
            'last_modified' => true,
            'content' => true,
            'name' => true,
            'author' => true,
            'creation_date' => true,
            'state' => true,
            'created_by' => true,
            'image_id' => true,
        );

	/**
	 * @var array _columns
	 * @access private
	 */
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


//		function init( $args = array() )
//		{
//			trigger_error('test');
//			$es = new entity_selector($this->site_id);
//			$es->add_type(id_of('baseball_roster_type'));
//			//$es->add_right_relationship( $this->page_id, relationship_id_of('site_to_baseball_roster'));
//			$result = $es->run_one();
//                        pray($result);
//			if ($result)
//			{
//				$result;
//                        }
//		}

                function init( $args = array() )
	{
//		if (empty($this->sport_id)) $this->sport_id = get_sport_id_by_page_id($this->page_id);
//		if ($this->sport_id) $this->sport = new entity($this->sport_id);
//		$this->parent->add_stylesheet('/global_stock/css/athletics/roster.css');
//
		$es = new entity_selector($this->site_id);
		$es->add_type(id_of('baseball_roster_type'));
		if (!empty($this->request['id']))
		{
			$es->add_relation('entity.id = ' . $this->request['id']);
		}
		else
		{
			//if ($this->sport_id) $es->add_left_relationship($this->sport->id(), relationship_id_of('player_to_sport'));
			$es->set_order('number, last_name, first_name'); // omit table name due to union query
		}
		$es->add_left_relationship_field( 'baseball_roster_to_image', 'entity', 'id', 'image_id', false); // get images and those with no image - uses union query

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
			$this->_add_crumb(  $pv['first_name'] . " " . $pv['last_name'] );
                        $this->_add_crumb(  $pv['name'] );
			$this->parent->title = 'Team Roster - Player';
		}
		$this->init_player_order();
	}

        function init_player_order()
	{
		//pray ($this->player_info);
		//die;
		if (!empty($this->request['player_sort']))
		{
			if ($this->request['player_sort'] == 'name_sort_asc')
			{
				$this->_player_sort = 'name';
				$this->_player_sort_order = 'asc';
				$this->aasort($this->player_info, 'name');
			}
			if ($this->request['player_sort'] == 'name_sort_desc')
			{
				$this->_player_sort = 'name';
				$this->_player_sort_order = 'desc';
				$this->aasort($this->player_info, 'name');
				$this->player_info = array_reverse($this->player_info, true);
			}
			if ($this->request['player_sort'] == 'number_sort_asc')
			{
				$this->_player_sort = 'number';
				$this->_player_sort_order = 'asc';
				$this->aasort($this->player_info, 'number', 'numerical');
			}
			if ($this->request['player_sort'] == 'number_sort_desc')
			{
				$this->_player_sort = 'number';
				$this->_player_sort_order = 'desc';
				$this->aasort($this->player_info, 'number', 'numerical');
				$this->player_info = array_reverse($this->player_info, true);
			}
			if ($this->request['player_sort'] == 'year_sort_asc')
			{
				$this->_player_sort = 'year';
				$this->_player_sort_order = 'asc';
				$this->aasort($this->player_info, 'class_year', 'class_year');
			}
			if ($this->request['player_sort'] == 'year_sort_desc')
			{
				$this->_player_sort = 'year';
				$this->_player_sort_order = 'desc';
				$this->aasort($this->player_info, 'class_year', 'class_year');
				$this->player_info = array_reverse($this->player_info, true);
			}
		}
		else
		{
			$this->_player_sort = 'number';
			$this->_player_sort_order = 'asc';
		}
	}

	function has_content()
	{
		if (!empty($this->player_info)) return true;
		elseif (!empty($this->request['baseball_roster']))
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
			if (isset($this->sport))
			{
				$coed = ($this->sport->get_value('gender') == 'Coed') ? true : false;
				$iterations = ($this->sport->get_value('gender') == 'Coed') ? 2 : 1;
			}
			else
			{
				$coed = false;
				$iterations = 1;
			}
			for( $i = 1; $i <= $iterations; $i++ )
			{
				if( $coed )
				{
					$str .= ( $this->gender[ $i ] == 'M' ) ? "<h4>Men's Roster</h4>" : "<h4>Women's Roster</h4>";
				}
				// now display the roster
				$str .= '<table class="athleticsRoster"><tr>';

				foreach ($this->_columns as $k => $v)
				{
					$str .= '<th class="rosterHead">';
					$str .= (($k == 'first_name') || ($k == 'number') || ($k == 'class_year') ) ? $this->gen_custom_header($k) : prettify_string ($k);
					$str .= '</th>';
				}
				$str .= '</tr>';

				$row = 1;

				// profiling
				//$s = get_microtime();

				foreach( $this->player_info as $k => $player )
				{
					if( !$coed OR $player['gender'] == $this->gender[ $i ] )
					{
						$row = 1 - $row;
						$str .= '<tr class="rosterRow'.($row + 1).'">';
						foreach( array_keys($this->_columns) as $col )
						{
							$str .= '<td class="rosterTD">';
							if( $col == 'first_name' )
							{
								$name = $player[$col].' '.$player['last_name'];
								if ( $player['image_id'] OR $player['content'] )
								{
									$player_link = carl_make_link(array('id' => $k, 'player_sort_order' => ''));
									$str .= '<a href="'.$player_link.'">'.$name.'</a>';
								}
								else $str .= $name;
							}
							else
								$str .= $player[$col];
							$str .= '</td>';
						}
						$str .= '</tr>';
					}
				}

				$str .= '</table>';
			}
			echo $str;
		}
	}
        function gen_custom_header($k)
	{
		if ($k == 'first_name')
		{
			$sort = (($this->_player_sort == 'name') && ($this->_player_sort_order == 'asc')) ? 'name_sort_desc' : 'name_sort_asc';
			$text = 'Name';
		}
		if ($k == 'number')
		{
			$sort = (($this->_player_sort == 'number') && ($this->_player_sort_order == 'asc')) ? 'number_sort_desc' : 'number_sort_asc';
			$text = 'Number';
		}
		if ($k == 'class_year')
		{
			$sort = (($this->_player_sort == 'year') && ($this->_player_sort_order == 'asc')) ? 'year_sort_desc' : 'year_sort_asc';
			$text = 'Class Year';
		}
		$link = carl_make_link(array('player_sort' => $sort));
		return '<a href="'.$link.'">'.$text.'</a>';
	}

        /**
	 * simple custom sorting function to get number, first_name, and class_year to display first
	 */
	function sort_columns(&$columns)
	{
		$sort_order = array('number' => 0, 'first_name' => 1, 'class_year' => 2); // specify whatever you want sorted in this manner
		$key_count = count($sort_order);
		foreach ($columns as $k=>$v)
		{
			if (isset($sort_order[$k])) $new_order[$sort_order[$k]] = $k;
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

	function aasort(&$x,$var,$cmp='strcasecmp')
	{
		if( !function_exists('numerical') )
		{
			function numerical($a,$b)
			{
				if (empty($a)) $a = '0';
				if (empty($b)) $b = '0';
				return $a-$b;
			}
		}
		if( !function_exists('class_year') )
		{
			function class_year($a,$b)
			{
				$values = array ('Fy.' => 1, 'So.' => 2, 'Jr.' => 3, 'Sr.' => 4);
				$a = (isset($values[$a])) ? $values[$a] : 0;
				$b = (isset($values[$b])) ? $values[$b] : 0;
				return $a-$b;
			}
		}
		if ( is_string($var) ) $var = "'$var'";
		uasort($x, create_function('$a,$b', 'return '.$cmp.'( $a['.$var.'],$b['.$var.']);'));
	}
}
?>
