<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'testModule';
	
	class testModule extends DefaultMinisiteModule
	{
		var $cleanup_rules = array(
			'email'=>'turn_into_string',
		);
		var $record = array();
		var $username = '';
		function init( $args = array() )
		{
			if(isset($this->request['email']))
			{
				$this->record = $this->get_record($this->request['email']);
			
			}
			if(!empty($this->record))
			{
				if($this->username = $this->create_account($this->record))
					if(!$this->send_email($record))
						trigger_error('Unable to send email');
			}
		}
		function get_record($email)
		{
			print "trying to connect";
			connectDB('admissions_accounts');
			// $record = array();
			print "connected";
			connectDB('reason_connection');
			print "back";


			// $handle = db_query('SELECT ...');
			// $record = mysql_fetch_assoc($handle);
			if($email == 'mryan@carleton.edu')
				return array();
			return array('id'=>1,'email'=>$email,'name'=>'Bob Sullivan',);

			// ----------------------------------------------------------------------------------------------



			// ----------------------------------------------------------------------------------------------

			


		}
		function create_account($record)
		{
			// figure out username
			$username = 'username';
			// create account
			// if success
			return $username;
			//else
			return false;
		}
		function send_email($record)
		{
			
			// pretend we send the email	
			return true;
		}
		function has_content()
		{
			return true;
		}
		function run()
		{
			if(empty($this->username))
			{
				$this->show_form();
			}
			else
			{
				$this->show_response();
			}
			//echo '<div class="test">Hello World</div>'."\n";
		}
		function show_form()
		{
			if(isset($this->request['email']) && empty($this->username) )
			{
				echo '<p class="error">Your email address wasn\'t found. Please check your spelling.</p>';
			}
			echo '<form method="post" action="?">'."\n";
			echo '<input type="text" value="'.(isset($this->request['email']) ? htmlspecialchars($this->request['email'], ENT_QUOTES) : '' ).'" name="email" />'."\n";
			echo '<input type="submit" value="Create my account!" />'."\n";
			echo '</form>'."\n";
		}
		function show_response()
		{
			echo '<p>Your username and password have been sent to '.htmlspecialchars($this->request['email'], ENT_QUOTES).'.</p>'."\n";
		}
	}
?>
 