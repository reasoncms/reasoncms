<?php
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'JensonMedalModule';
reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'classes/object_cache.php' );
include_once(DISCO_INC.'disco.php');

class JensonMedalModule extends DefaultMinisiteModule {
		var $form;
		var $logged_user;
		var $usernames = array(
'adamem03', 'adhiem01', 'adhibi01', 'adixst01', 'adubba01', 'ahmena01', 'alemri01',
'alpeal01', 'andesa04', 'andeda11', 'andejo17', 'andeal04', 'archel01', 'armspa02',
'armspa03', 'atarya01', 'atkipa01', 'ayinmi01', 'babcje01', 'babeal01', 'bachka02',
'bahnry01', 'baldda01', 'bandfi01', 'barkja01', 'barkni01', 'barrwi01', 'batial01',
'bauske01', 'beansy01', 'bearka02', 'beckan03', 'behram01', 'beiepe01', 'bellmi01',
'bentla01', 'benzme01', 'bergbr06', 'bergjo06', 'bergco01', 'berkem01', 'berlha01',
'bertad01', 'bharas01', 'biebsa01', 'bisbsc01', 'blaija01', 'blazca01', 'blesma01',
'blocja02', 'blocka01', 'boehda01', 'boghad01', 'boghal01', 'bolebr01', 'bormca01',
'boroco01', 'bottka01', 'boumel01', 'bourje01', 'bourmi01', 'braakr01', 'bradke01', 'brayph01',
'brodma01', 'broide01', 'brower02', 'burkaa01', 'burraa01', 'butlbl01', 'bygdha01', 'byrnjo02',
'cahlba01', 'canokr01', 'carlpa04', 'carlam02', 'carnka01', 'casper01', 'casska01', 'cervar01',
'charev01', 'chensh01', 'chenma01', 'chriab01', 'chrima09', 'cichho01', 'clafca01', 'claida01',
'claral03', 'clarbr03', 'cochem01', 'comepa01', 'connje02', 'conwra01', 'coolca02', 'gundtr01',
'creame01', 'crissa01', 'criser01', 'croaal01', 'czecca01', 'czecsa01', 'dahlan02', 'danead01',
'darlbr01', 'darlma01', 'davier03', 'deldta01', 'delhma01', 'amosje01', 'demmha01', 'devini01',
'devori01', 'diazta01', 'diebka01', 'dienge01', 'dietam01', 'dietke01', 'dimijo01', 'dirtda01',
'dirtja01', 'divili01', 'donlke01', 'dormam01', 'dostse01', 'dotsjo01', 'dotske02', 'downde02',
'dralbr01', 'drewst02', 'drisbr01', 'drisch01', 'dudlkr01', 'duinse01', 'durnca01', 'easa01',
'earlja01', 'edwasa02', 'eggeal01', 'egguer01', 'ehrele01', 'eitrer01', 'eldrli01', 'emerje01',
'eminch01', 'endeja01', 'engeja06', 'ericca06', 'ericje05', 'ericme01', 'etteka01', 'everbr01',
'ewinsa01', 'ewyna01', 'feenca01', 'feltsa01', 'fercch01', 'fercha01', 'fettry01', 'finaan01',
'fincbr02', 'fishni01', 'fitzia01', 'flucda01', 'footco01', 'formke01', 'foutja01', 'fransh03',
'franza01', 'freiro01', 'froeta01', 'fullza01', 'furein01', 'fyfeva01', 'gaermi01', 'gammal01',
'gardbe01', 'gardla02', 'garrhi01', 'gartma01', 'gaulca01', 'gautri01', 'gednch01', 'gendaa01',
'geraal01', 'geribr02', 'gescka01', 'getcli01', 'gianna01', 'gibbda01', 'gillem01', 'gislal01',
'goncma01', 'gonzan01', 'goodka03', 'grafpe01', 'graida01', 'greesp01', 'griejo02', 'grifla01',
'grosas01', 'grunha01', 'grundr01', 'guntco01', 'guoru01', 'habtwo01', 'hagawi01', 'hageer01',
'halvje01', 'hammch03', 'hammka02', 'hanha01', 'hancbe01', 'hanski07', 'hansch06', 'hanuem01',
'skelel01', 'harrel02', 'hashka01', 'heinma01', 'hendal01', 'hertna01', 'hillmi02', 'hobbbr01',
'hoffaa01', 'hoffjo02', 'hoffmi02', 'hoffwi01', 'holdky01', 'holmkr01', 'holmsa01', 'holmty01',
'holsca01', 'holtjo07', 'houmka01', 'huffke01', 'hughji01', 'humpjo01', 'humpjo02', 'huneke01',
'hurlty01', 'husspa01', 'hylaka01', 'hylake01', 'ihnska01', 'ilikha01', 'imhoma01', 'irisma01',
'jackjo04', 'janaha01', 'janjra01', 'jazwch01', 'jeddma01', 'jeffem01', 'johnbe07', 'johnbr10',
'johnel08', 'johnem05', 'johnem06', 'johner11', 'johnka21', 'jonech03', 'jordbe01', 'jungsa01',
'kallam01', 'kangja01', 'kaspju01', 'kaufca01', 'kenial01', 'kessst01', 'keyer01', 'kingle02',
'kircka02', 'kirkme01', 'kittke01', 'klaero01', 'kleish01', 'klimke01', 'klinmo01', 'klinro01',
'knigan02', 'knopka01', 'knutel02', 'knutje01', 'kochda02', 'kochjo01', 'kofobe01', 'kortda01',
'kostbe02', 'kraube01', 'kraupe02', 'krebch01', 'krieab01', 'krumbr01', 'kustmi02', 'kuttan01',
'kwatdi01', 'lafojo01', 'larkja01', 'larsda08', 'larsem01', 'larski02', 'lavesa01', 'ledeke01',
'leejo08', 'lenopa01', 'leutab01', 'librsa01', 'limpsa01', 'lindan02', 'linder05', 'lindau01',
'llitan01', 'lohfal01', 'lokesi01', 'loofan01', 'lundha01', 'lutcbi01', 'lyncch01', 'lyncel01',
'lynnem01', 'lyonza01', 'maaska01', 'macdme01', 'magnpe01', 'maleab01', 'maleka03', 'mallje01',
'kopier01', 'maroer01', 'marsju02', 'martab01', 'martja03', 'masith01', 'mattop01', 'mattro01',
'mattas02', 'matter02', 'mcalsa01', 'mccame03', 'mccomi01', 'mcdori02', 'mceawh01', 'mcfake01',
'mchemo01', 'mckeky01', 'mcknse01', 'mcleja01', 'mcveem01', 'mcwier01', 'medfla01', 'mehlka02',
'meirda02', 'merrti01', 'mescmi01', 'meyeau01', 'meyeco01', 'meyesa06', 'michma04', 'mickma02', 'mietje01',
'milldi01', 'mitcke01', 'moanka01', 'moenma02', 'molsca01', 'molzma01', 'coutam01', 'morami01',
'mulhli01', 'mummel01', 'murran01', 'myhrry01', 'mykler01', 'nancab01', 'nasiso01', 'nelsko01',
'neumch01', 'niehca01', 'nielkr03', 'nikapr01', 'noltmi01', 'normka01', 'nybepa01', 'obeymi01',
'ofstpa02', 'oldfan01', 'omeast01', 'oswada01', 'otteja01', 'paljju01', 'palmka02', 'papkan01',
'parvse01', 'paulbe02', 'peckan02', 'pedeme01', 'pedebr02', 'plasal01', 'pollje01', 'portdo01',
'procte01', 'proean01', 'punkha01', 'quanma03', 'rainjo01', 'rasmem02', 'ratean01', 'ravead01',
'redimi01', 'reutla01', 'reyebr01', 'reyema01', 'reynch02', 'reynma02', 'reynsy01', 'richli03',
'riefam01', 'ripler01', 'rittel01', 'rittre01', 'rohdin01', 'roseaa01', 'rothem01', 'rowlan01',
'ruther01', 'ryanli01', 'sailch01', 'sakyci01', 'samuje01', 'samura01', 'sanci01', 'sancni01',
'sancal01', 'sandam03', 'schaia01', 'scheal02', 'schijo04', 'schica03', 'schlme01', 'schlka04',
'schlka03', 'schmaa02', 'schmme02', 'schmja02', 'schnal02', 'schnal03', 'schora01', 'schrai01',
'schrem02', 'schuco01', 'schuet01', 'schula05', 'schura03', 'secomi01', 'seebca01', 'seibja01',
'sengma01', 'sharma01', 'sharka01', 'shieju01', 'siemgr01', 'sikona01', 'simaca01', 'simpke01',
'skogta01', 'sloaan01', 'smalja01', 'smitaa02', 'smitch02', 'smorch01', 'snodas01', 'snydje02',
'snydla01', 'snydst02', 'sodemi01', 'sojksa01', 'sonkch01', 'sonnka01', 'sorgca01', 'spooem01',
'sprima01', 'steesh01', 'stefdu01', 'stegal01', 'stenjo02', 'stevel01', 'stevty01', 'stocki01',
'struju01', 'stuaka02', 'stumma02', 'sturan01', 'styksa01', 'sundsc01', 'swanha01', 'swanja03',
'sweeal01', 'switab01', 'tangra01', 'teslji01', 'thaitr01', 'thokpa01', 'thomso01', 'thomth05',
'thorjo03', 'thormi01', 'tiento01', 'tinjem01', 'tomeal01', 'tuckkr01', 'tulkmo01', 'tullbe01',
'turema01', 'usanlo01', 'vanbis01', 'vanhty01', 'vandal03', 'vazqel01', 'vermal01', 'vinzry01',
'visthe01', 'vivalo01', 'voeler01', 'voriqu01', 'vossad01', 'wadmer01', 'wagnka01', 'walsan01',
'wardja02', 'wardda01', 'warnwh01', 'watsja02', 'weatbe01', 'weavli02', 'weckan01', 'weekre01',
'wegmli01', 'westkr01', 'wettma01', 'wettpa01', 'wheeca01', 'whipma02', 'wietkr01', 'wilcda01',
'wilkja03', 'wilsam01', 'windje01', 'winkdo01', 'winsno01', 'wittja01', 'wojcan01', 'woocal01',
'woodaa01', 'wrenal01', 'wulfda02', 'xiexi01', 'yahrli01', 'yeakje01', 'yindi01', 'zahrti01',
'zehrra01', 'zengyi01', 'zenoka01', 'zeyty01', 'zimmel01', 'zinnty01',
		);
		
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
//				force_secure();

				parent::init( $args );
				if ($head_items =& $this->get_head_items()) {
						$head_items->add_stylesheet('/reason/jquery-ui-1.8.12.custom/css/redmond/jquery-ui-1.8.12.custom.css');
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
//						echo $choice1_ex[1].'<br>';
//						echo in_array($choice1_ex[1], $this->usernames).'-';


						if (in_array($choice1_ex[1], $this->usernames) === false){
								$this->form->set_error('first_choice', '<strong>' . $choice1 .'</strong> is not an elligible nominee. Please use the autocomplete list to populate your choice.');
						}
						$choice2_ex = explode(', ', $choice2);
						if (in_array($choice2_ex[1], $this->usernames) === false){
								$this->form->set_error('second_choice', '<strong>' . $choice2 .'</strong> is not an elligible nominee. Please use the autocomplete list to populate your choice.');
						}
						$choice3_ex = explode(', ', $choice3);
						if (in_array($choice3_ex[1], $this->usernames) === false){
								$this->form->set_error('third_choice', '<strong>' . $choice3 .'</strong> is not an elligible nominee. Please use the autocomplete list to populate your choice.');
						}
				}
		}
		
		function on_every_time(){
				$this->logged_user = reason_check_authentication();
//				$this->logged_user = 'adamem03';
				$this->logged_user = 'andesa04';
				
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
				
				
				
				
								
				
		}
		
		function run(){
//				$this->display_form();
				$this->form->run();
		}
		
		function process(){
				connectDB('jenson_medal_connection');
				$qstring = "SELECT * FROM `nominees` WHERE `username`='" . mysql_real_escape_string($this->logged_user) . "' ";
				
				$results = db_query($qstring);
//				 	if (mysql_num_rows($results) < 1) {
//						$qstring = "INSERT INTO `applicants` (`open_id`, `creation_date`, `submitter_ip`)
//							VALUES ('" . mysql_real_escape_string($openid) . "', NOW(), '" . $_SERVER['REMOTE_ADDR'] . "'); ";
//						$results = mysql_query($qstring) or die(mysql_error());
//						$qstring = "SELECT * FROM `applicants` WHERE `open_id`='" . mysql_real_escape_string($openid) . "' ";
//						$results = db_query($qstring);
//					}
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
//							if ($element == 'ssn_1') {
//								if ($the_form->get_value('ssn_1') || $the_form->get_value('ssn_2') || $the_form->get_value('ssn_3')) {
//									$qstring .= "`ssn` = '" . mysql_real_escape_string($the_form->get_value('ssn_1')) . "-" . mysql_real_escape_string($the_form->get_value('ssn_2')) . "-" . mysql_real_escape_string($the_form->get_value('ssn_3')) . "', ";
//								}
//							}
						}
						// ssn is 3 individual form elements, combine and write to db
						$qstring .= "`submitted_date`=NOW(), `has_voted`='Y'";
				//        $qstring = rtrim($qstring, ' ,');
						$qstring .= " WHERE `username`= '" . mysql_real_escape_string($this->logged_user) . "' ";
						//die($qstring);
					}
					$qresult = db_query($qstring);
				
				
				connectDB(REASON_DB);
		}
		
		function display_thankyou(){
				$this->form->show_form = false;
				echo "Thank you for voting. Please <a href='/login/?logout=1'>logout</a>.";
		}
}

?>
