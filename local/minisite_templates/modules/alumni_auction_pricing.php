<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'testModule';
	
	class testModule extends DefaultMinisiteModule
	{
		function init( $args = array() )
		{


		// force secure
		if( !on_secure_page() )
		{		
		
			header("Location: https://reason.luther.edu/getdowngiveback/register/");
			exit;
		}

		}
	
		function has_content()
		{
			return true;
		}
		function run()
		{
			$this->show_form();
		}
		
		function show_form()
		{
			//$ticket30 = $this->request['ticket30'];
			//$ticket50 = $this->request['ticket50'];
			//$ticket100 = $this->request['ticket100'];
			//$table1000 = $this->request['table1000'];


			if ( isset( $_POST['ticket30'] ) ) { @$ticket30 = $_POST['ticket30']; } else { $ticket30='';}
			if ( isset( $_POST['ticket50'] ) ) { @$ticket50 = $_REQUEST['ticket50']; } else { $ticket50='';}
			if ( isset( $_POST['ticket100'] ) ) { @$ticket100 = $_REQUEST['ticket100']; } else { $ticket100='';}
			//if ( isset( $_POST['table1000'] ) ) { @$table1000 = $_REQUEST['table1000']; } else { $table1000='';}
			if ( isset( $_POST['donation'] ) ) { @$donation = $_REQUEST['donation']; }  else { $donation='';}

			$donation = str_replace( '$' , '' ,  $donation );



			if(isset($this->request['email']) && empty($this->username) )
			{
				echo '<p class="error">Your email address wasn\'t found. Please check your spelling.</p>';
			}
			//echo '<form method="post" action="?">'."\n";
			//echo '<input type="text" value="'.(isset($this->request['email']) ? htmlspecialchars($this->request['email'], ENT_QUOTES) : '' ).'" name="email" />'."\n";
			//echo '<input type="submit" value="Create my account!" />'."\n";
			//echo '</form>'."\n";

			echo '<form method="post" action="?">';
			echo '<table width="400" border="1" >';
			echo '  <tr>';
			echo '    <td>Tickets at $30 (admission only)</td>';
			echo '    <td>';
			echo '      <select name="ticket30">';
			echo '        <option value="0">None</option>';
			for ( $counter = 1; $counter <= 20; $counter += 1) 
			{
				echo '        <option';
					if ( $ticket30 == $counter ) { echo ' selected '; }
				echo '>' . $counter . '</option>';
			}
			echo '      </select>';
			echo '    </td>';
			echo '  </tr>';
			echo '  <tr>';
			echo '    <td>Tickets at $50 (admission, valet, parking, and a $10 tax deductible gift to the TCYA scholarship)<br><br></td>';
			echo '    <td><select name="ticket50">';
			echo '      <option value="0">None</option>';
			for ( $counter = 1; $counter <= 20; $counter += 1) 
			{
				echo '        <option';
					if ( $ticket50 == $counter ) { echo ' selected '; }
				echo '>' . $counter . '</option>';
			}
			echo '    </select></td>';
			echo '  </tr>';
			echo '  <tr>';
			echo '    <td>Tickets at $100 (admission, valet, parking, and a $60 gift tax deductible gift to the TCYA scholarship)<br><br></td>';
			echo '    <td><select name="ticket100">';
			echo '      <option value="0">None</option>';
			for ( $counter = 1; $counter <= 20; $counter += 1) 
			{
				echo '        <option';
					if ( $ticket100 == $counter ) { echo ' selected '; }
				echo '>' . $counter . '</option>';
			}
			echo '    </select></td>';
			echo '  </tr>';
			/*echo '  <tr>';
			echo '    <td>Table at $1000 </td>';
			echo '    <td><select name="table1000">';
			echo '      <option value="0">None</option>';
			for ( $counter = 1; $counter <= 4; $counter += 1) 
			{
				echo '        <option';
					if ( $table1000 == $counter ) { echo ' selected '; }
				echo '>' . $counter . '</option>';
			}
			echo '    </select></td>';
			echo '  </tr>';
			*/
			echo '  <tr>';
			echo '    <td>Additional outright gift to the <br>Twin Cities Young Alumni Scholarship Fund<br>Thank You for Your Support!<br><br></td>';
			echo '    <td><nobr>$<input name="donation" type="text" size="8" value=' . $donation . '></nobr></td>';
			echo '  </tr>';
			echo '</table>';
			echo '<p>';
			echo '  <input type="submit" name="Recalculate Total" value="Recalculate Total">';
			echo '  <br>';
			echo '  <br>';
			echo '</p>';
			echo '</form>';





			$total = 0;
			$textout = '';

			if ( $ticket30 > 0 ) { $textout = $textout . 'Tickets at $30: ' . $ticket30 . '<br>'; $total = $total + $ticket30 * 30; }
			if ( $ticket50 > 0 ) { $textout = $textout . 'Tickets at $50: ' . $ticket50 . '<br>'; $total = $total + $ticket50 * 50; }
			if ( $ticket100 > 0 ) { $textout = $textout . 'Tickets at $100: ' . $ticket100 . '<br>'; $total = $total + $ticket100 * 100; }
			//if ( $table1000 > 0 ) { $textout = $textout . 'Table at $1000: ' . $table1000 . '<br>'; $total = $total + $table1000 * 1000; }
			if ( $donation > 0 ) { $textout = $textout . 'Donation: $' . $donation . '<br>'; $total = $total + $donation; }

			if ( strlen($textout) > 0 )
			{
			$textout = $textout . '<br>';
			$textout = $textout . 'Total Amount $' . $total . '<br>';
			echo '<hr height="2">';
			echo $textout;
			echo '<br>';
			echo '<form method="post" action="/getdowngiveback/register/2/">';
			echo '  <input type="hidden" name="ccprepopulate" value="true">';
			echo '  <input type="hidden" name="ccpaymentamount" value="$' . $total . '">';
			//echo '  <input type="hidden" name="ccpaymentdetail" value="' . str_replace("<br>", "\n" , $textout) . '">';
			echo '  <input type="hidden" name="ccpaymentdetail" value="' . $textout . '">';
			echo '  <input type="submit" name="submit" value="Continue to Next Page">';
			echo '<form>';

			}
		}
	}
?>
 