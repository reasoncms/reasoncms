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
			//$ticket40 = $this->request['ticket40'];
			//$ticket75 = $this->request['ticket75'];
			//$ticket100 = $this->request['ticket100'];
			//$ticket200 = $this->request['ticket200'];


			if ( isset( $_POST['ticket40'] ) ) { @$ticket40 = $_POST['ticket40']; } else { $ticket40='';}
			if ( isset( $_POST['ticket75'] ) ) { @$ticket75 = $_REQUEST['ticket75']; } else { $ticket75='';}
			if ( isset( $_POST['ticket100'] ) ) { @$ticket100 = $_REQUEST['ticket100']; } else { $ticket100='';}
			if ( isset( $_POST['ticket200'] ) ) { @$ticket200 = $_REQUEST['ticket200']; } else { $ticket200='';}
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
			echo '    <td>Tickets at $40 <br>(Includes admission and a $5 tax-deductible gift to the TCYA scholarship)</td>';
			echo '    <td>';
			echo '      <select name="ticket40">';
			echo '        <option value="0">None</option>';
			for ( $counter = 1; $counter <= 20; $counter += 1) 
			{
				echo '        <option';
					if ( $ticket40 == $counter ) { echo ' selected '; }
				echo '>' . $counter . '</option>';
			}
			echo '      </select>';
			echo '    </td>';
			echo '  </tr>';
			
			echo '  <tr>';
			echo '    <td>Tickets at $75 <br>(Includes admission and a $40 tax-deductible gift to the TCYA scholarship)<br><br></td>';
			echo '    <td><select name="ticket75">';
			echo '      <option value="0">None</option>';
			for ( $counter = 1; $counter <= 20; $counter += 1) 
			{
				echo '        <option';
					if ( $ticket75 == $counter ) { echo ' selected '; }
				echo '>' . $counter . '</option>';
			}
			echo '    </select></td>';
			echo '  </tr>';
			
			echo '  <tr>';
			echo '    <td>Tickets at $100 <br>(Includes admission and a $65 tax-deductible gift to the TCYA scholarship)<br><br></td>';
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
			
			echo '  <tr>';
			echo '    <td>Tickets at $200 <br>(Includes admission and a $165 tax-deductible gift to the TCYA scholarship)<br><br></td>';
			echo '    <td><select name="ticket200">';
			echo '      <option value="0">None</option>';
			for ( $counter = 1; $counter <= 20; $counter += 1) 
			{
				echo '        <option';
					if ( $ticket200 == $counter ) { echo ' selected '; }
				echo '>' . $counter . '</option>';
			}
			echo '    </select></td>';
			echo '  </tr>';
			
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

			if ( $ticket40 > 0 ) { 
				$textout .= 'Tickets at $40: ' . $ticket40 . '<br>';
				$total = $total + $ticket40 * 40; 
			}
			if ( $ticket75 > 0 ) { 
				$textout .= 'Tickets at $75: ' . $ticket75 . '<br>'; 
				$total = $total + $ticket75 * 75; 
			}
			if ( $ticket100 > 0 ) { 
				$textout .= 'Tickets at $100: ' . $ticket100 . '<br>'; 
				$total = $total + $ticket100 * 100; 
			}
			if ( $ticket200 > 0 ) { 
				$textout .= 'Tickets at $200: ' . $ticket200 . '<br>'; 
				$total = $total + $ticket200 * 200; 
			}
			if ( $donation > 0 ) { 
				$textout .= 'Donation: $' . $donation . '<br>'; 
				$total = $total + $donation; 
			}

			if ( strlen($textout) > 0 )
			{
			$textout .= '<br>';
			$textout .= 'Total Amount $' . $total . '<br>';
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
 