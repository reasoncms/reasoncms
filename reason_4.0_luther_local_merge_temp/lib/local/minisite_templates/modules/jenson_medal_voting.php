<?php
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'JensonMedalModule';
reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'classes/object_cache.php' );
include_once(DISCO_INC.'disco.php');

class JensonMedalModule extends DefaultMinisiteModule {
		var $form;
		var $logged_user;
		
		var $elements = array(
			'your_name' => 'text',
			'instructions' => array(
				'type' => 'comment',
				'text' => 'Enter the name or username of the person you\'d like to nominate. 
						Please use the auto-complete feature to select the name.'
			),
			'first_choice' => 'text',
			'first_choice_username' => 'hidden',
			'second_choice' => 'text',
			'second_choice_username' => 'hidden',
			'third_choice' => 'text',
			'third_choice_username' => 'hidden',
		);
		
		var $custom_magic_transform_attributes = array('your_name');
		
		var $required = array('your_name', 'first_choice', 'second_choice', 'third_choice');
		
		function init( $args = array() ){
				parent::init( $args );
				if ($head_items =& $this->get_head_items()) {
						$head_items->add_stylesheet('/reason/jquery-ui-1.8.12.custom/css/redmond/jquery-ui-1.8.12.custom.css');
						$head_items->add_javascript('/jquery/jquery_ui_latest.js');
						$head_items->add_javascript('/reason/js/jenson_medal.js');
				}

				$this->form = new disco();
				$this->form->elements = $this->elements;
				$this->form->actions = array('Vote!');
				$this->form->error_header_text = 'Error';
				$this->form->add_callback(array(&$this, 'display_thankyou'),'process');
				$this->form->add_callback(array(&$this, 'run_error_checks'),'run_error_checks');
				$this->form->add_callback(array(&$this, 'on_every_time'),'on_every_time');
				$this->form->add_callback(array(&$this, 'process'),'process');
				$this->form->init();

				$url_parts = parse_url( get_current_url() );
		}
		
		function run_error_checks(){
				$choice1 = $this->form->get_value('first_choice');
				$choice2 = $this->form->get_value('second_choice');
				$choice3 = $this->form->get_value('third_choice');
				
				if (!$choice1 || !$choice2 || !$choice3){
						$this->form->set_error('first_choice', 'Please make 3 choices.');
				} else {

						if (strcasecmp($choice1, $choice2) == 0 || strcasecmp($choice1, $choice3) == 0 || strcasecmp($choice2, $choice3) == 0){
								$this->form->set_error('first_choice', 'Please choose 3 different classmates.');
						}
						$choice1_ex = explode(', ', $choice1);
						
						connectDB('jenson_medal_connection');
						$qstring = "SELECT `username` from `nominees`";
						$results = db_query($qstring);
						connectDB(REASON_DB);
						
						
						while ($row = mysql_fetch_array($results)) {
								$nominees[] = $row['username'];
						}
						
						if (in_array($choice1_ex[1], $nominees) === false){
								$this->form->set_error('first_choice', '<strong>' . $choice1 .'</strong> is not an elligible nominee. Please use the autocomplete list to populate your choice.');
						}
						$choice2_ex = explode(', ', $choice2);
						if (in_array($choice2_ex[1], $nominees) === false){
								$this->form->set_error('second_choice', '<strong>' . $choice2 .'</strong> is not an elligible nominee. Please use the autocomplete list to populate your choice.');
						}
						$choice3_ex = explode(', ', $choice3);
						if (in_array($choice3_ex[1], $nominees) === false){
								$this->form->set_error('third_choice', '<strong>' . $choice3 .'</strong> is not an elligible nominee. Please use the autocomplete list to populate your choice.');
						}
				}
		}
		
		function on_every_time(){
				$this->logged_user = reason_check_authentication();

				/*
				 * get data from db
				 */
				connectDB('jenson_medal_connection');
				$qstring = "SELECT * from `nominees` where `username` = '" . mysql_real_escape_string($this->logged_user) . "' ";
				$results = db_query($qstring);
				$row = mysql_fetch_array($results, MYSQL_ASSOC);
				connectDB(REASON_DB);
				
				$formatted_name = $row['first_name'] . ' ' . $row['last_name'];
				/*
				 * if inelligible user accidentally gets here
				 */
				if (!$row) {
						echo '<div style="padding:30px">You are not eligible to vote. If you feel this is an error,
								please contact <a href="mailto:einckmic@luther.edu">Michelle Einck</a> in the Development Office, x1862.</div>';
						$this->form->show_form = false;
				}
				/*
				 * if user has already voted, display a message 
				 */
				if (!is_null($row['has_voted'])) {
						$this->form->show_form = false;
						echo '<div>Logged in as: ' . $formatted_name . '</div>';
						echo '<div style="padding:30px">It appears that you\'ve already submitted your votes. If you feel this is an error,
								please contact <a href="mailto:einckmic@luther.edu">Michelle Einck</a> in the Development Office, x1862.</div>';
				} else { /* else, let them vote */
						$this->form->change_element_type('your_name', 'solidtext');
						$this->form->set_value('your_name', $formatted_name);
				}
				echo "<a href='/login/?logout=1'>Logout</a>";
				
				if ($this->logged_user == 'smitst01' || $this->logged_user == 'einckmic' || $this->logged_user == 'jonebr01'){
						self::display_results();
				}
		}
		
		function run(){
				$this->form->run();
		}
		
		function process(){
				connectDB('jenson_medal_connection');
				$qstring = "SELECT * FROM `nominees` WHERE `username`='" . mysql_real_escape_string($this->logged_user) . "' ";
				
				$results = db_query($qstring);
					while ($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
						$qstring = "UPDATE `nominees` SET ";
						foreach ($this->form->get_element_names() as $element) {
							if (array_key_exists($element, $row)) {
								$qstring .= $element . "=";
								if ((!is_null($this->form->get_value($element))) && ($this->form->get_value($element) <> '')) {
									if (is_array($this->form->get_value($element))) {
										$qstring .= "'" . mysql_real_escape_string(implode(',', $this->form->get_value($element))) . "'";
									} else {
										$qstring .= "'" . mysql_real_escape_string($this->form->get_value($element)) . "'";
									}
								} else {
									$qstring .= 'NULL';
								}
								$qstring .= ", ";
							}
						}
						$qstring .= "`submitted_date`=NOW(), `has_voted`='Y'";
						$qstring .= " WHERE `username`= '" . mysql_real_escape_string($this->logged_user) . "' ";
					}
					$qresult = db_query($qstring);
				
				
				connectDB(REASON_DB);
		}
		
		function display_thankyou(){
				$this->form->show_form = false;
				echo "Thank you for voting. Please <a href='/login/?logout=1'>logout</a>.";
		}
		
		function display_results(){
				$this->form->show_form = false;
				echo '<div id="results"><h2>RESULTS</h2>';
				
				connectDB('jenson_medal_connection');
				$query = "SELECT has_voted FROM nominees WHERE has_voted IS NOT NULL";
				$results = db_query($query);
				$voters =  mysql_num_rows($results);
				
				$query = "SELECT * FROM nominees";
				$results = db_query($query);
				$names = array();
				while ($row = mysql_fetch_array($results)) {
						$names[$row['username']] = $row['first_name'] . ' ' . $row['last_name'];
				}
				$total =  mysql_num_rows($results);
				$percent = $voters / $total * 100;
				echo '<p>' . round($percent) . '% have voted (' . $voters . '/' . $total . ')</p>';
				
				$tallied_votes = array();
				
				$query = "SELECT * FROM first_choices_view";
				$results = db_query($query);
				while ($row1 = mysql_fetch_array($results)) {
						$tallied_votes[$row1['first_choice_username']] = array('tallied' => $row1['c']*3, 'first' => $row1['c']);
						
				}
				$query = "SELECT * FROM second_choices_view";
				$results = db_query($query);
				while ($row2 = mysql_fetch_array($results)) {
						if (isset ($tallied_votes[$row2['second_choice_username']]['tallied'])){
								$tallied_votes[$row2['second_choice_username']]['tallied'] = $tallied_votes[$row2['second_choice_username']]['tallied'] + $row2['c']*2;
								$tallied_votes[$row2['second_choice_username']]['second'] = $row2['c'];
						} else {
								$tallied_votes[$row2['second_choice_username']]['tallied'] = $row2['c']*2;
								$tallied_votes[$row2['second_choice_username']]['second'] = $row2['c'];
						}
				}
				$query = "SELECT * FROM third_choices_view";
				$results = db_query($query);
				while ($row3 = mysql_fetch_array($results)) {
						if (isset ($tallied_votes[$row3['third_choice_username']]['tallied'])){
								$tallied_votes[$row3['third_choice_username']]['tallied'] = $tallied_votes[$row3['third_choice_username']]['tallied'] + $row3['c'];
								$tallied_votes[$row3['third_choice_username']]['third'] = $row3['c'];
						} else {
								$tallied_votes[$row3['third_choice_username']]['tallied'] = $row3['c'];
								$tallied_votes[$row3['third_choice_username']]['third'] = $row3['c'];
						}
				}
				
				
				$str = '<table id="votes" class="tablesorter" border="0" cellpadding="0" cellspacing="0">';
				$str .= '<thead>';
                $str .= '<tr>';
				$str .= '<th>Username</th>';
				$str .= '<th>Tallied Votes</th>';
				$str .= '<th>1st Place Votes</th>';
				$str .= '<th>2nd Place Votes</th>';
				$str .= '<th>3rd Place Votes</th>';
                $str .= '</tr>';
				$str .= '</thead>';
				$str .= '<tbody>';
				foreach ($tallied_votes as $k => $v) {
						$str .= '<tr class="">';
						$str .= '<td class="username">' . $names[$k] . '</td>';
						$str .= '<td class="tallied">' . $v['tallied'] . '</td>';
						$str .= '<td class="first_place">' . (isset($v['first']) ? $v['first'] : '') . '</td>';
						$str .= '<td class="second_place">' . (isset($v['second']) ? $v['second'] : '') . '</td>';
						$str .= '<td class="third_place">' . (isset($v['third']) ? $v['third'] : '') . '</td>';
				}
				$str .= '</tbody>';
				$str .= '</table>';
				echo $str;
		
				connectDB(REASON_DB);
				
		}
}

?>
