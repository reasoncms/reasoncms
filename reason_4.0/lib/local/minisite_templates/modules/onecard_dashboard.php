<?php
    $GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'OnecardDashboardModule';
    reason_include_once( 'minisite_templates/modules/default_with_auth.php' );
    include_once(CARL_UTIL_INC.'dir_service/directory.php');
    include_once('onecard.php');
    
class OnecardDashboardModule extends DefaultMinisiteWithAuthModule
{

	var $user_colleagueid;
	var $user_patronid;
	var $onecard;
	var $dir;
	var $patron_info;
	// parameters that can be passed in the URL
	var $cleanup_rules = array(
		'id' => array('function' => 'turn_into_int'),
                'netid' => array('function' => 'turn_into_string'),
		'start' => array('function' => 'turn_into_string'),
		'end' => array('function' => 'turn_into_string'),
		'lost' => array('function' => 'turn_into_string'),
	);

	function init( $args = array() )
	{
		$this->redir_link_text = 'the OneCard dashboard';
		$this->msg_uname = 'onecard_login_blurb';
		
		parent::init( $args );
		
		$this->dir = new directory_service(array('ldap_carleton'));
		$this->onecard = new onecard;
		if( isset( $this->parent->textonly ) && $this->parent->textonly == 1 )
		{
			$this->link_str = '&textonly=1';
		}
		if (THIS_IS_A_DEVELOPMENT_REASON_INSTANCE && !empty($this->request['netid'])) { 
			$this->user_netID = $this->request['netid']; 
		} else {
			$this->request['netid'] = '';
		}
		// Look up user and determine CS Gold PIK
		$this->set_user_colleagueid();
		if ($this->onecard->db->is_connected()) {
			if (THIS_IS_A_DEVELOPMENT_REASON_INSTANCE && isset($this->request['id'])) 
			{
				$this->user_patronid = $this->request['id'];
			} else {
				$PIK = $this->onecard->colleague_to_PIK($this->user_colleagueid);
				$this->user_patronid = $this->onecard->PIK_to_patron_id($PIK);
			}
			if ($this->user_patronid) $this->patron_info = $this->onecard->get_patron_info($this->user_patronid);
			// If they got here by marking their card lost or found, make that change.
			if (!empty($this->request['lost'])) { 
				$this->onecard->toggle_card_is_lost($this->user_patronid);
				if ($this->onecard->get_card_is_lost($this->user_patronid)) 
					$this->notify_support_staff('lost');
				else
					$this->notify_support_staff('found');
			}
		}
	}
 
	// Look up user's college ID in the campus directory
	function set_user_colleagueid() {
		if (!empty ($this->user_netID )) {
			if ($this->dir->search_by_attribute('carlnetid', $this->user_netID, array('carlcolleagueid'))) {
				if ($this->user_colleagueid = $this->dir->get_first_value('carlcolleagueid')) return true;
			}
		}	
		return false;	
	}
	
	// Primary page display
	function run()
	{
		



			echo "username: " . $this->request['id'] . "---";


			if (!$this->onecard->db->is_connected()) {
			echo 'The OneCard database is not currently available. Please try again later.';
			return;
		}
		
		if (empty($this->user_patronid) || !is_array($this->patron_info)) {
			pray($this->dir);
			echo 'An error occurred accessing your record.  Please try again or contact the OneCard office.';
			return;
		}
		
		// On the test server, we need to allow spoofed identities for testing
		$extra_params = (THIS_IS_A_DEVELOPMENT_REASON_INSTANCE) ? sprintf('netid=%s&id=%s&', $this->request['netid'], $this->user_patronid) : '';

		// Figure out date ranges for transaction displays and create date navigation links	
		$start = (empty($this->request['start'])) ? mktime(0, 0, 0, date("m"), date("d")-13,  date("Y")) : $this->request['start'];
		$end = (empty($this->request['end'])) ? mktime(0, 0, -1, date("m"), date("d")+1,  date("Y")) : $this->request['end'];
		
		$backlink = (date('d M Y', $start) == date('d M Y')) ? '' : 
			sprintf('<a href="?%sstart=%s&end=%s" title="Earlier transactions">&lt;&lt;</a> ', $extra_params, ($start-86400*14), ($end-86400*14));
		$forlink = (date('d M Y', $end) == date('d M Y')) ? '' : 
			sprintf(' <a href="?%sstart=%s&end=%s" title="Later transactions">&gt;&gt;</a> ', $extra_params, ($start+86400*14), ($end+86400*14));
		$datenav = sprintf( '<p>%s%s - %s%s</p>', $backlink, date('d M Y', $start), date('d M Y', $end), $forlink);
		
		// Find the right card image background and display card with user photo overlaid
		$image = ($this->patron_info[0]['CLASSIFICATION'] == 'STUDENT') ? id_of('onecard_blank_student').'.jpg' :  id_of('onecard_blank_staff').'.jpg';
		echo '<div class="dashboard">';
		echo '<div class="card_area">';
		echo '<div class="card_image"><img src="/reason_package/reason_4.0/www/images/'.$image.'"  />';
		printf( '<div class="role">%s</div>', $this->patron_info[0]['CLASSIFICATION']);
		printf( '<div class="name_on_card">%s %s</div>', $this->patron_info[0]['FIRSTNAME'], $this->patron_info[0]['LASTNAME']);
		printf( '<img src="/stock/onecard-image.php?id=%d" class="user_image" /></div>', $this->user_patronid);
		printf('<div class="post_card"><p>College ID Number: %s</p></div>', $this->user_colleagueid);
		echo '</div>';
		
		// Provide card status info and Lost/Found link
		echo '<div class="activation_notice">';
		if ($this->patron_info[0]['ACTIVE'] == '1') {
			if ($this->onecard->get_card_is_lost($this->user_patronid)) {
				echo '<form method="post">';
				echo get_text_blurb_content('dashboard_lost_card_blurb');
				echo '<p><input type="submit" name="lost" value="Reenable my card" /></p>';
			} else {
				printf ('<form method="post" onSubmit="return confirm(\'%s\')">', addslashes(strip_tags(get_text_blurb_content('dashboard_lost_card_final_warning'))));
				echo get_text_blurb_content('dashboard_active_card_blurb');
				echo '<p><input type="submit" name="lost" value="Report my card as lost" /></p>';
			}
		} else {
			echo get_text_blurb_content('dashboard_inactive_card_blurb');
		}
		echo "</form></div>";
		
		// Show stored value balances and transactions
		echo '<h3>Stored Value Plans</h3>';
		echo '<h4>Current Balances</h4>';
		$balances = $this->onecard->get_svc_balance($this->user_patronid);
		foreach ($balances as $bal) {
			printf('<p>%s: $%01.2f</p>', $bal['DESCRIPTION'], ($bal['AVAILBALANCE']/100));
			// save plan info for transaction display
			$svc_plans[$bal['PLANID']] = $bal['DESCRIPTION'];
		}
		echo '<p><a href="../add_funds/?_step=CreditPageThreeForm&cardholderid='.$this->user_netID.'">Add funds to my card</a></p>';
		echo '<h4>Transaction History</h4>';
		echo $datenav;
		$ledger = $this->onecard->get_patron_ledger($this->user_patronid, $start, $end);
		if (count($ledger)) {
			echo '<table class="transaction_table">';
			echo '<tr><th>Date</th><th>Time</th><th>Location</th><th>Amount</th><th>Plan</th><th>Comment</th></tr>';
			$lastdate = '';
			foreach ($ledger as $trans) {
				// only show transactions we have SVC plan info for (sometimes meal plans with flex dollars will show up in the data)
				if (!isset($svc_plans[$trans['ACCOUNTNUMBER']])) continue;
				$credit = ($trans['APPRVALUEOFTRAN'] < 0) ? ' credit' : '';
				$displaydate = ($trans['TRANDAY'] == $lastdate) ? '' : $trans['TRANDAY'];
				$lastdate = $trans['TRANDAY'];
				printf('<tr><td class="date">%s</td><td class="time">%s</td><td class="location">%s</td><td class="%samount">%s $%01.2f</td><td class="comment">%s</td><td class="comment">%s</td></tr>',
					$displaydate, $trans['TRANTIME'], $trans['LONGDES'], $credit, $credit, abs($trans['APPRVALUEOFTRAN'])/100, $svc_plans[$trans['ACCOUNTNUMBER']], $trans['THECOMMENT']);
			}
		echo '</table>';
		} else {
			echo  "<p><em>(No transactions found)</em></p>";
		}
		
		// Show stored value balances and transactions (if applicable)
		$plans = $this->onecard->get_patron_mealplans($this->user_patronid);
		if (count($plans)) {
			echo '<h3>Meal Plans</h3>';
			echo '<h4>Current Balances</h4>';
			foreach ($plans as $plan) {
				$planlabel = $plan['DESCRIPTION'];
				if ($plan['MEALSPERWEEK']) $planlabel .= ' ('.$plan['MEALSPERWEEK'].' meals per week)';
				printf('<p>%s</p><ul><li>Meals remaining today: %d</li>', $planlabel, $plan['SMALLBUCKET']);
				if ($plan['MEALSREMAININGDEF'] == 2) printf ('<li>Meals remaining this period: %d</li>',$plan['SMALLBUCKET']);
				if ($plan['MEALSREMAININGDEF'] == 3) printf ('<li>Meals remaining this week: %d</li>',$plan['MEDIUMBUCKET']+$plan['SMALLBUCKET']);
				if ($plan['MEALSREMAININGDEF'] == 4) printf ('<li>Meals remaining this term: %d</li>',$plan['LARGEBUCKET']+$plan['MEDIUMBUCKET']+$plan['SMALLBUCKET']);
				echo '</ul>';
			}
			echo '<h4>Transaction History</h4>';
			echo $datenav;
			$result = $this->onecard->get_meal_ledger($this->user_patronid, $start, $end);
			if (count($result)) {
				echo '<table class="transaction_table">';
				echo '<tr><th>Date</th><th>Time</th><th>Meal</th><th>Location</th></tr>';
				$lastdate = '';
				foreach ($result as $trans) {
					$credit = ($trans['SMALLBUCKETAMOUNT'] < 0) ? ' credit' : '';
					$displaydate = ($trans['TRANDAY'] == $lastdate) ? '' : $trans['TRANDAY'];
					$lastdate = $trans['TRANDAY'];
					printf('<tr><td class="date">%s</td><td class="time">%s</td><td class="meal%s">%s%s</td><td class="location">%s</td></tr>',
						$displaydate, $trans['TRANTIME'], $credit, $trans['PERIODNAME'], $credit, $trans['LONGDES']);
				}
			echo '</table>';
			} else {
				echo  "<p><em>(No transactions found)</em></p>";
			}
		}
		echo '</div>';
	}
	
	// Send email to staff who need to be notified of Lost/Found cards
	function notify_support_staff($status = 'lost')
	{
		$recipient = (THIS_IS_A_DEVELOPMENT_REASON_INSTANCE) ? 'mheiman@carleton.edu' : 'lost_onecard_notify@carleton.edu';
		$headers = "From: wsg@carleton.edu\r\n";
		if ($status == 'lost') {
			$subject = sprintf('Lost OneCard reported for %s %s, %s', 
				$this->patron_info[0]['FIRSTNAME'], $this->patron_info[0]['LASTNAME'], $this->patron_info[0]['CLASSIFICATION']);
			$body = sprintf("Lost OneCard reported from the OneCard Dashboard for %s %s\nPIK:%s", 
				$this->patron_info[0]['FIRSTNAME'], $this->patron_info[0]['LASTNAME'], $this->patron_info[0]['PRIMARYKEY']);
		} else {
			$subject = sprintf('Recovered OneCard reported for %s %s, %s', 
				$this->patron_info[0]['FIRSTNAME'], $this->patron_info[0]['LASTNAME'], $this->patron_info[0]['CLASSIFICATION']);
			$body = sprintf("Recovered OneCard reported from the OneCard Dashboard for %s %s\nPIK:%s", 
				$this->patron_info[0]['FIRSTNAME'], $this->patron_info[0]['LASTNAME'], $this->patron_info[0]['PRIMARYKEY']);
		}			
		mail($recipient, $subject, $body, $headers);
	}
		
}



?>
