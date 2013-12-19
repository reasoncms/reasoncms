<?php
/**
 * Script that sends reminders about publications that have not been recently updated
 *
 * Run this script from the command-line (probably as a cron task) like this:
 *
 * /path/to/php -d include_path=/path/to/reason_package/ /path/to/reason_package/reason_4.0/lib/core/scripts/news/tickler_many.php
 *
 * No arguments are supplied - they are fetched from the database using entity selectors.
 *
 * This script can also be accessed through the browser, like so:
 * http://servername.org/path/to/reason/www/scripts/news/tickler_many.php
 *
 *
 * @package reason
 * @subpackage scripts
 * @author Ben White
 */

//  Include dependencies
include_once('reason_header.php');
include_once('tyr/email.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/user_functions.php');

class reasonPublicationReminder
{
	// Central initiator function. Checks that $pub is an acceptable variable, 
	// then sets $this->pub and runs the set days and set emails functions.
	function set_publication($pub)
	{
		if(is_numeric($pub))
		{
			$id = (integer) $pub;
		}
		elseif(reason_unique_name_exists($pub))
		{
			$id = id_of($pub);
		}
		else
		{
			die('The publication unique name provided ('.$pub.') does not exist'."\n");
		}
		$this->pub = new entity($id);
		if(!$this->pub->get_values())
		{
			die('The publication provided does not exist'."\n");
		}
		if($this->pub->get_value('type') != id_of('publication_type'))
		{
			die('The publication provided is not, in fact, a publication'."\n");
		}
		$this->set_reminder_days( $this->pub->get_value('reminder_days'));
		$this->set_reminder_emails($this->pub->get_value('reminder_emails'));
	}

	// Checks that $days is numeric, then sets $this->days to the int value of $days. Run by set_publication
	function set_reminder_days($days)
	{
		if(is_numeric($days))
		{
			$this->days = (integer) $days;
		}
		else
		{
			die('The number of days provided '. $days . ' is not numeric.');
		}
	}

	// Checks that $emails is not empty, then sets $this->emails to $emails. Run by set_publication
	function set_reminder_emails($emails)
	{
		if(!empty($emails))
		{
			$this->emails = $emails;
		}
		else
		{
			die('Emails are not set.');
		}
	}

	// Main active function. set_publication must have been run before this.
	// Calculates the days since a post, uses that to find the days since a reminder.
	// Outputs one of three outcomes:
	// If no new post on the publication and it has been a week since a reminder, it sends an email.
	// If no new post, but a message has been sent earlier, it does not send an email.
	// If there has been a new post, it does not send an email.
	function remind()
	{
		$days_since_post = $this->get_days_since_post();
		if($days_since_post == -1)
		{
			echo 'No posts on publication id ' . $this->pub->id() . ' ('.$this->pub->get_value('name').').'."\n".'<br>';
		}
		$days_since_first_reminder = $days_since_post - $this->days;
		if($days_since_first_reminder >= 0)
		{
			$days_since_weekly_reminder = $days_since_first_reminder % 7;
		}
		else
		{
			$days_since_weekly_reminder = -1;
		}
		if($days_since_weekly_reminder==0)
		{
			echo 'No new posts on publication id ' . $this->pub->id() . ' ('.$this->pub->get_value('name').') since ' . $this->recent_date . '.'."\n";
			$email = new Email($this->emails, 'no-reply@carleton.edu', 'no-reply@carleton.edu', 'Reason Publication Reminder', $this->get_email_message(), nl2br($this->get_email_message()));
			$email->send();
			echo 'Message sent to '.$this->emails."\n".'<br>';
		}
		elseif(!($days_since_weekly_reminder==-1))
		{
			echo 'No new posts on publication id ' . $this->pub->id() . ' ('.$this->pub->get_value('name').') since ' . $this->recent_date . ', but a message has been sent within the past week.'."\n";
			echo 'No message sent'."\n".'<br>';
		}
		elseif(!($days_since_post==-1))
		{
			echo 'New post on publication id ' . $this->pub->id() . ' ('.$this->pub->get_value('name').') since ' . $this->recent_date . '.'."\n";
			echo 'No message sent'."\n".'<br>';
		}	
	}	
	
	// Returns the message that forms the body of the email sent in remind()
	function get_email_message()
	{
		$message = 'FYI, there are currently no recent posts on the Reason publication "' . $this->pub->get_value('name') . '."'."\n\n";
		$message .= 'You are signed up to receive notices when this publication has not been updated in the last '.$this->days.' days.'."\n\n";
		$message .= 'It may be time to add a new post! ';
		$message .= 'Click here to add posts to this publication: http://'.REASON_WEB_ADMIN_PATH.'?site_id='.get_owner_site_id( $this->pub->id() ).'&type_id='.id_of('news')."\n\n";
		$message .= 'If you are no longer responsible for this publication, please contact a Reason administrator to have this email sent to someone else.'."\n\n";
		$message .= 'Thank you!'."\n\n";
		return $message;
	}
	
	// Returns the number of days since the last post on a publication.
	function get_days_since_post()
	{
		$es = new entity_selector();
		$es->add_type(id_of('news'));
		$es->add_left_relationship( $this->pub->id(), relationship_id_of('news_to_publication'));
		$es->set_num(1);
		$es->set_order('datetime DESC');
		$posts = $es->run_one();
		if (!empty($posts))
		{
			foreach ($posts as $id=>$entity)
			{
				$this->recent_date = $entity->get_value('datetime');
			}
		}
		else
		{
			return -1;
		}
		date_default_timezone_set('America/Chicago');
		$date = date('Y-m-d H:i:s');
		return $this->date_difference($this->recent_date, $date);
	}

	// Returns the number of days between two dates given in date format.
	function date_difference($date_1, $date_2)
	{
		$date_2 = date_create($date_2);
		$temp = date_create($date_1);
		$count = 0;
		while(($temp < $date_2)&&$count < 500)
		{
			$temp->modify('+1 day');
			$count++;
		}
		return $count;
	}
	
}
// Checks for user, requires authentication for non-cli users.
if(PHP_SAPI == 'cli')
{
	$user = 'causal_agent';
}
else
{
	$user = reason_require_authentication();
	$reason_user_id = get_user_id( $user );
	if (!reason_user_has_privs( $reason_user_id, 'db_maintenance' ) )
	{
		die('Access denied.'."\n");
	}
}
// Creates a list of the  publications with reminder_days set above 0.
$es = new entity_selector();
$es->add_type(id_of('publication_type'));
$es->add_relation('`reminder_days` > 0');
$publications = $es->run_one();
// For each publication, make sure the site owning it is live, then create a reminder, set pulication for that reminder, and run the remind function.
foreach($publications as $pub_id=>$pub)
{		
	$sites = $pub->get_right_relationship('site_owns_publication_type');
	$to_show = false;
	foreach($sites as $index => $site)
	{
		if($site->get_value('site_state')=='Live')
		{
			$to_show = true;
		}
	}
	if($to_show)
	{
		$test = new reasonPublicationReminder;	
		$test->set_publication($pub_id);
		$test->remind();	
	}
}

// That's all, folks!
?>
