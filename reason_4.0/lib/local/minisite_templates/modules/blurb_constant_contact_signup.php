<?php
	reason_include_once( 'minisite_templates/modules/blurb.php' );
        reason_include_once( 'classes/constant_contact/add_contact.php');

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ConstantContactSignupBlurbModule';
	
	class ConstantContactSignupBlurbModule extends BlurbModule
	{
		var $newsletter_codes = array(
		'Luther Alumni Magazine (3x/year)' => array('username' => 'lcmagazine', 'listname' => 'E-Magazine Reminder', 'token' => "68d44a70-3e25-4c8d-aac7-73ce9e5e35fc"),
		'College Ministries "For the Common Good" (monthly)' => array('username' => 'lcchapel', 'listname' => 'For the Common Good', 'token' => "d8296e3f-4097-4380-9136-7e83f6885d5e"),
		'Parent Newsletter (monthly)' => array('username' => 'lcparents', 'listname' => 'Parents of Luther College Newsletter', 'token' => "41513ddc-bbac-4e16-bb11-44f0e0228ae4"),
		'The Bulletin (Tu, F)' => array('username' => 'lcbulletin', 'listname' => 'Bulletin', 'token' => "e069031c-ed27-4583-bfdf-13a7624da435"),
		'Diversity Today (monthly)' => array('username' => 'lcdiversity', 'listname' => 'Diversity Today', 'token' => "ef688df7-c24b-4ac1-826d-68d6ed22bd16"),
		'Luther College Chapel (M, W, F)' => array('username' => 'lcchapel', 'listname' => 'Upcoming In Chapel', 'token' => "d8296e3f-4097-4380-9136-7e83f6885d5e"), //The list name here may be changing in the near future
		'Luther News (weekly)' => array('username' => 'lccampusnews', 'listname' => 'Luther News', 'token' => "1082bbed-d18c-442f-9f45-f109d4c71888"),
		'Music Connections (weekly)' => array('username' => 'lcmusicmarketing', 'listname' => 'Music Connections', 'token' => "0bea60bc-b224-4104-85b4-6187e42784e2"),
		'Sustainability (2x/month)' => array('username' => 'lcsustainability', 'listname' => 'Newsletter', 'token' => "cc938f38-031e-40bb-a4e6-bc548e2f4799"),
		'Baseball' => array('username' => 'lcbaseball', 'listname' => 'Luther College Baseball Newsletter', 'token' => "887408da-ffa7-4223-b742-3c1770bb4b56"),
		'Men\'s Basketball' => array('username' => 'lcmbasketball', 'listname' => 'Luther College Men\'s Basketball Newsletter', 'token' => "51a3b3fd-ff76-4a29-8197-b22f2cd418ca"),
		'Women\'s Basketball' => array('username' => 'lcwbasketball', 'listname' => 'Luther College Women\'s Basketball Newsletter', 'token' => "81dd621f-847f-4a3b-9f94-a29ec759a872"),
		'Men\'s Cross Country' => array('username' => 'lccrosscountry', 'listname' => 'Luther College Men\'s Cross Country Newsletter', 'token' => "f2b76f59-6251-43ac-9c00-70ecc1f8dc1d"),
		'Women\'s Cross Country' => array('username' => 'lccrosscountry', 'listname' => 'Luther College Women\'s Cross Country Newsletter', 'token' => "f2b76f59-6251-43ac-9c00-70ecc1f8dc1d"),
		'Football' => array('username' => 'lcfootball1', 'listname' => 'Luther College Football Newsletter', 'token' => "67b2beef-b91f-439c-904d-ea518242cb41"),
		'Men\'s Golf' => array('username' => 'lcmgolf', 'listname' => 'Luther College Men\'s Golf Newsletter', 'token' => "c562309e-bc96-4c53-9cff-c349e630f5bd"),
		'Women\'s Golf' => array('username' => 'lcwgolf', 'listname' => 'Luther College Women\'s Golf Newsletter', 'token' => "e2f1c67a-c275-4f8a-8afb-332e9f36041e"),
		'Men\'s Soccer' => array('username' => 'lcmenssoccer', 'listname' => 'Luther College Men\'s Soccer Newsletter', 'token' => "2a4ab5fd-8edd-417a-92c4-1967b57ad062"),
		'Women\'s Soccer' => array('username' => 'lcwsoccer', 'listname' => 'Luther College Women\'s Soccer Newsletter', 'token' => "8aa9f9f9-90c4-41b0-87fe-a61f429c8e2a"),
		'Softball' => array('username' => 'lcsoftball', 'listname' => 'Luther College Softball Newsletter', 'token' => "115fc0e1-73dc-4c51-b2dd-e099b02522e9"),
		'Swimming and Diving' => array('username' => 'lcswimming', 'listname' => 'Luther College Swimming and Diving Newsletter', 'token' => "d2439e02-e73c-480a-b25f-0d20c9ff4edf"),
		'Tennis' => array('username' => 'lctennis', 'listname' => 'Luther College Tennis Newsletter', 'token' => "51f68ba5-1381-4f46-967c-e388265ef1e4"),
		'Track and Field' => array('username' => 'lctrack', 'listname' => 'Luther College Track and Field Newsletter', 'token' => "df76086d-34db-4f85-a25b-5e0b5f6d5017"),
		'Volleyball' => array('username' => 'lcvolleyball', 'listname' => 'Luther College Volleyball Newsletter', 'token' => "af0829e6-eb70-407d-ba69-71b2f17ac48c"),
		'Wrestling' => array('username' => 'lcwrestling', 'listname' => 'Luther College Wrestling Newsletter', 'token' => "0dd9679c-4085-4522-94e4-e59166f05a3b"),
	);
		var $newsletter_names = array( 
			'lcmagazine' => 'Luther Alumni Magazine (3x/year)', 
			'forthecommongood'=>'College Ministries "For the Common Good" (monthly)', 
			'lcparents'=>'Parent Newsletter (monthly)',
			'lcbulletin'=>'The Bulletin (Tu, F)',
			'lcdiversity'=>'Diversity Today (monthly)',
			'lcchapel'=>'Luther College Chapel (M, W, F)',
			'lccampusnews'=>'Luther News (weekly)',
			'lcmusicmarketing'=>'Music Connections (weekly)',
			'lcsustainability'=>'Sustainability (2x/month)',
			'lcbaseball'=>'Baseball',
			'lcmbasketball'=>'Men\'s Basketball',
			'lcwbasketball'=>'Women\'s Basketball',
			'lccrosscountrymen'=>'Men\'s Cross Country',
			'lccrosscountrywomen'=>'Women\'s Cross Country',
			'lcfootball1'=>'Football',
			'lcmgolf'=>'Men\'s Golf',
			'lcwgolf'=>'Women\'s Golf',
			'lcmenssoccer'=>'Men\'s Soccer',
			'lcwsoccer'=>'Women\'s Soccer',
			'lcsoftball'=>'Softball',
			'lcswimming'=>'Swimming and Diving',
			'lctennis'=>'Tennis',
			'lctrack'=>'Track and Field',
			'lcvolleyball'=>'Volleyball',
			'lcwrestling'=>'Wrestling',
		);

		function run() // {{{
		{
			$inline_editing =& get_reason_inline_editing($this->page_id);
			$editing_available = $inline_editing->available_for_module($this);
			$editing_active = $inline_editing->active_for_module($this);
			echo '<div class="blurbs ccBlurb ' . $this->get_api_class_string() . '">'."\n";
			$i = 0;
			$class = 'odd';
			foreach( $this->blurbs as $blurb )
			{
				if (preg_match("/constant_contact_signup_blurb/", $blurb->get_value('unique_name')))
				{
					$editable = ( $editing_available && $this->_blurb_is_editable($blurb) );
					$editing_item = ( $editing_available && $editing_active && ($this->request['blurb_id'] == $blurb->id()) );
					$i++;
					$uniqueName = $blurb->get_value('unique_name');
					$uniqueNameArr = explode("_",$uniqueName);	
					if($_POST["ccEmail"] == "" || !preg_match("/@.+\..+/", $_POST["ccEmail"]) || $_POST['ccEmailList']==""){
						if(count($uniqueNameArr) > 5){
							echo '<h3>Signup for our Email Newsletter(s)</h3>';
						}
						elseif(count($uniqueNameArr) == 5){
							$listName = $this->newsletter_codes[$this->newsletter_names[$uniqueNameArr[4]]]['listname'];
							echo '<h3>'.$blurb->get_value('name').'</h3>';
						}
						echo '<div class="blurb number'.$i;
						if($blurb->get_value('unique_name'))
							echo ' uname_'.htmlspecialchars($blurb->get_value('unique_name'));
						if( $editable )
							echo ' editable';
						if( $editing_item )
							echo ' editing';
						echo ' '.$class;
						echo '"';
						if( $blurb->get_value('content') == ""){
							echo ' style="display: none;" ';
						}
						echo '>';
						echo '<div class="blurbInner">';
					
						if($editing_item)
						{
							if($pages = $this->_blurb_also_appears_on($blurb))
							{	
								$num = count($pages);
								echo '<div class="note"><strong>Note:</strong> Any edits you make here will also change this blurb on the '.$num.' other page'.($num > 1 ? 's' : '').' where it appears.</div>';
							}
							echo $this->_get_editing_form($blurb);
						}
						else
						{
							echo demote_headings($blurb->get_value('content'), $this->params['demote_headings']);
							if( $editable )
							{
								$params = array_merge(array('blurb_id' => $blurb->id()), $inline_editing->get_activation_params($this));
								echo '<div class="edit"><a href="'.carl_make_link($params).'">Edit Blurb</a></div>'."\n";
							}	
						}

						echo '</div>'."\n";
						echo '</div>'."\n";
						$class = ('odd' == $class) ? 'even' : 'odd';	
						if($_SERVER["REQUEST_METHOD"]=="POST" && (!preg_match("/@.+\..+/", $_POST["ccEmail"]) || $_POST["ccEmailList"]=="")){
							echo "<div id='discoErrorNotice'><h3 style='color:red'>Your form has errors</h3><ul>";
							if(!preg_match("/@.+\..+/", $_POST["ccEmail"])){
								echo"<li class='ccFormError'>Error: Please use a valid email address</li><br>";
							}
							if($_POST["ccEmailList"] == ""){
								echo"<li class='ccFormError'>Error: Please select at least one list</li>";
							}
							echo "</ul></div>";
						}
						echo '<form method="POST">
							<div class="formElement">
								<div class="words">
									<span class="labelText">Email Address</span>
									<span class="requiredIndicator">
										<span title="required">*</span>
									</span>
								</div>
								<div class="element">
									<input type="text" name="ccEmail" id="ccEmail" class="text" required>
								</div>
							</div>';
						if(count($uniqueNameArr)==5){
							echo'<div class="formElement hideCCCheckbox">';
						}
						else{
							echo'<div class="formElement">';
						}		
						echo'		<div class="words">
									<span class="labelText">Newsletters</span>
									<span class="requiredIndicator">
										<span title="required">*</span>
									</span>
								</div>
								<div class="element">
									<div class="checkBoxGroup">
										<table border="0" cellpadding="1" cellspacing="0">
											<tbody>';	
						for($x=4; $x<count($uniqueNameArr); $x+=1){
							$newsletterName = $this->newsletter_names[$uniqueNameArr[$x]];
							$newsletterInfo = $this->newsletter_codes[$newsletterName];
							echo '<tr><td valign="top"><input type="checkbox" id="'.$uniqueNameArr[$x].'" name="ccEmailList[]" value="'.$uniqueNameArr[$x].'"';
							if(count($uniqueNameArr)==5){
								echo' checked ';
							}
							echo'"></td><td valign="top"><label for="'.$uniqueNameArr[$x].'">'.$newsletterName.'</label></td></tr>';
						}
						echo '</tbody></table></div></div></div>';
						echo '<input type="submit" value="Submit">';
					}
					else{
						echo '<div class="ccSuccess">';
						echo '<p class="ccThankYou">Thank you for signing up for the following email newsletter(s)</p>';
						if(is_array($_POST["ccEmailList"])){
							$emailLists = $_POST["ccEmailList"];
						}
						else{
							$emailLists = array($_POST["ccEmailList"]);
						}
						$email = $_POST['ccEmail'];
						echo '<ul>';
						foreach($emailLists as $emailName){
							$emailTitle = $this->newsletter_names[$emailName];
							$emailInfo = $this->newsletter_codes[$emailTitle];
							echo '<li>'.$emailInfo['listname'].'</li>';
							add_contact($emailInfo['token'], $emailInfo['listname'], $email);	
						}
						echo '</ul>';
						echo '</div>';	
					}
				}
			}
			echo '</div>'."\n";
		}

		function has_content()
		{
			if(!empty($this->blurbs))
			{
				foreach($this->blurbs as $blurb)
				{
					if (preg_match("/constant_contact_signup_blurb/", $blurb->get_value('unique_name')))
					{
						return true;
					}
				}
			}
			return false;
		}
		
	}
?>
