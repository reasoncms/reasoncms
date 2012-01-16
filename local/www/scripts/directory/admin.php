<?php

/******************************************
		admin.php
		Elliot Jordan, jordel01@luther.edu
		June-July 2005
 ******************************************/

require_once("./functions.php");
require_once("./config.php");
require_once('./head.html');

// CONFIG VARIABLES
$show_debug = false; // If show_debug is true, SQL queries will be displayed, and no e-mails will be sent
$requests_per_page = 30; // Number of requests per page displayed (on completed requests page)

// added by Steve Smith
// for using this script with the Reason directory
$_SESSION['user'] = $_GET['name'];
if (stripos($_SERVER['HTTP_COOKIE'], "REASON_SESSION") && (stripos($_SERVER['HTTP_REFERER'], "reasondev.luther.edu/newdirectory/"))){
	$_SESSION['logged_in'] = 1;
	$_SESSION['not_anon'] = 1;
	$_SESSION['is_admin'] = 1;
	$_SESSION['user'] = $_GET['name'];
}


$ds = @ldap_connect($ldap_server, $ldap_port);

if (!$ds) {
  echo "Unable to connect to the LDAP server";
  require_once("./foot.html");
  exit(1);
}

$res = @ldap_bind($ds, $_SESSION['userdn'], $_SESSION['pass']);
  
if (!$res) {
  echo "Unable to bind to the LDAP server";
  require_once("./foot.html");
  exit(1);
}

// Display "You are logged in as" bar (swiped from functions.php)
?>
<table border="0" cellpadding="1" cellspacing="0" width="100%">
  <tr bgcolor=#eeeeee>
    <td nowrap colspan=2>
<?php
 
if (isset($_SESSION['logged_in'])) {
  if ($_SESSION['user'] == '') {
    echo "Logged in as <b>anonymous</b>";
  } else {
    echo "Logged in as <b>".$_SESSION['user']."</b>";
  }
} else {
  echo "&nbsp;";
}
?>
    </td>
    <td align=right nowrap>
    </td>
  </tr>
</table><br />
<?php

// Must be logged in as an administrator

if (!isset($_SESSION['logged_in']) || $_SESSION['user'] == '' || !is_admin($ds,$_SESSION['user'])) {
  echo "You are not allowed in this area!";
  require_once("./foot.html");
  exit(1);
}

  echo '<div align="center"><a href="admin.php?mode=pending&name='.$_SESSION['user'].'">Pending Requests</a>'.
  	   ' | <a href="admin.php?mode=completed&name='.$_SESSION['user'].'">Completed Requests</a></div>';

  $debug = '<h1>Debug</h1>'; // Start accumulating debug output
  	   
  /******************************************
	      Handle SUBMITTED alias requests  
   ******************************************/

  if ($_GET['mode'] == 'submitted') {
    $debug .= 'You submitted these changes:<blockquote><small>';
    
    // Connect to database
    $link = mysql_connect($email_alias_host, $email_alias_username, $email_alias_password);
    if (!$link) {
		  echo 'Could not connect to '.$email_alias_database.' database: ' . mysql_error();
		  require_once("./foot.html");
		  exit(1);
		} else {
			mysql_select_db($email_alias_database);
		}

    // See if we have any $_POST variables to process, and then process them
    $i = 0;
    while (isset($_POST['requestID_'.$i])) {
      if (isset($_POST['status_'.$i])) {
        $query = "UPDATE ".$email_alias_table." SET status = '".$_POST['status_'.$i]."' WHERE requestID = '".$_POST['requestID_'.$i]."'";
        $result = mysql_query($query);
        $debug .= $query.'<br />';
        
        // Record reviewer
        $query = "UPDATE ".$email_alias_table." SET reviewer = '".$_SESSION['user']."' WHERE requestID = '".$_POST['requestID_'.$i]."'";
        $result = mysql_query($query);
        $debug .= $query.'<br />';
        
        // Record statusDate
        $query = "UPDATE ".$email_alias_table." SET statusDate = '".date('Y-m-d G:i:s')."' WHERE requestID = '".$_POST['requestID_'.$i]."'";
        $result = mysql_query($query);
        $debug .= $query.'<br />';

        // E-mail user if their request has been approved or denied
        if ($_POST['status_'.$i] == 'app' || $_POST['status_'.$i] == 'den') {
          if ($_POST['status_'.$i] == 'app') { $mailstatus = 'APPROVED'; }
          if ($_POST['status_'.$i] == 'den') { $mailstatus = 'DENIED'; }
          $mailquery = "SELECT username, alias FROM ".$email_alias_table." WHERE requestID = '".$_POST['requestID_'.$i]."'";
          $mailresult = mysql_query($mailquery);
          
          // Prepare mail parameters
          $mailuser  = mysql_result($mailresult, 0, 'username').'@luther.edu';
          $mailsubj  = 'Your alternate email request status';
          $mailmesg  = 'Your request for "'.mysql_result($mailresult, 0, 'alias').'@luther.edu" has been '.$mailstatus.'.
          
If you have further questions, please contact the LIS Help Desk at 1000, or e-mail helpdesk@luther.edu.
You may be interested in more information regarding issues pertaining to 
your account, there are further instructions
available at http://lis.luther.edu/node/4012';
          $mailhead  = "MIME-Version: 1.0\n";
          $mailhead .= "Content-type: text/plain; charset=iso-8859-1\n";
          $mailhead .= "X-Priority: 3\n";
          $mailhead .= "X-MSMail-Priority: Normal\n";
          $mailhead .= "X-Mailer: php\n";
          $mailhead .= "From: \"LIS Help Desk\" <helpdesk@luther.edu>\n";
       // $mailhead .= "Bcc: ".$_SESSION['user']."@luther.edu\n"; // Copy mail to reviewer
          
          // Send mail if debug mode is off
          if (!$show_debug) {
            mail($mailuser,$mailsubj,$mailmesg,$mailhead);
          } else {
            $debug .= '<br />'.$mailquery.'<br /><em>Mail would have been sent to '.$mailuser.' ('.$mailstatus.')</em><br />';
            
          }
          
          // Modify LDAP information if approved
          if ($_POST['status_'.$i] == 'app') {
            
            unset($entry);
            
            $name = mysql_result($mailresult, 0, 'username');
            
	    // try to call google to add nickname ------------------------------------------------------------------------

		$migrateusername = mysql_result($mailresult, 0, 'username');
		$proposednickname = mysql_result($mailresult, 0, 'alias');

		#print "Norse Mail trying to add $proposednickname to $migrateusername<br>";
		#die;

		$data = array ('username' => $migrateusername, 'proposednickname' => $proposednickname, 'key' => 'cow9999');
		$data = http_build_query($data);

		$context_options = array (
			'http' => array (
	            	'method' => 'POST',
		       'header'=> "Content-type: application/x-www-form-urlencoded\r\n"
		        . "Content-Length: " . strlen($data) . "\r\n",
		        'content' => $data
		            )
		        );

		$context = stream_context_create($context_options);

		#$fp = fopen('https://webmail.luther.edu/src/googleconvert_addnickname.php', 'r', false, $context);
		$fp = fopen('https://norsekey.luther.edu/norsemailremote/googleconvert_addnickname.php', 'r', false, $context);

		$contents = stream_get_contents($fp);

		print $contents;



	     // end google code ------------------------------------------------------------------------



            $attr = array("uid",
                    "mailLocalAddress",
                    "mailRoutingAddress");
                    
            $filter = "(uid=$name)";
                    
            $ldapresult = @ldap_search($ds, $peopledn, $filter, $attr);
            $user = @ldap_get_entries($ds, $ldapresult);
            
            for ($j = 0; $j < count($user[0]['maillocaladdress']) - 1; $j++) {
              $entry['mailLocalAddress'][$j] = $user[0]['maillocaladdress'][$j];
            }
            $entry['mailLocalAddress'][$j] = mysql_result($mailresult, 0, 'alias').'@luther.edu';
            
            if (is_array($user[0]['maillocaladdress'])) {
              @ldap_mod_replace($ds, "uid=$name,$peopledn", $entry);
            } else {
              if (count($entry['mailLocalAddress'])) {
                @ldap_mod_add($ds, "uid=$name,$peopledn", $entry);
              }
            }
            
            unset($entry);
            
            // set mailRoutingAddress to luther assigned email
            //if (count($user[0]['maillocaladdress']) - 1 > 0) {
              //$entry['mailRoutingAddress'] = $user[0]['maillocaladdress'][0];
              $entry['mailRoutingAddress'] = $name . "@luther.edu";
              if (is_array($user[0]['mailroutingaddress'])) {
                @ldap_mod_replace($ds, "uid=$name,$peopledn", $entry);
              } else {
                //if (count($entry['mailRoutingAddress'])) {
                  @ldap_mod_add($ds, "uid=$name,$peopledn", $entry);
                //}
              }
            //}

            unset($entry);

            $debug .= 'LDAP: '.mysql_result($mailresult, 0, 'alias').'@luther.edu added to mailLocalAddress of '.$name.'<br />';
            
          } // End if ($_POST['status_'.$i] == 'app')
          $debug .= '<br />';
        }
      }
      if (isset($_POST['notes_'.$i])) {
        $query = "UPDATE ".$email_alias_table." SET note = '".$_POST['notes_'.$i]."' WHERE requestID = '".$_POST['requestID_'.$i]."'";
        $result = mysql_query($query);
        $debug .= $query.'<br />';
      }
      $i = $i + 1;
    }
    
    mysql_close($link); // Disconnect from database
    
    echo "<div align='center'><br />Your changes have been submitted. Thank you.<br /></div>";
    
    $debug .= '</small></blockquote>';
  }
  	   
  /******************************************
	  Screen to display PENDING alias requests  
   ******************************************/
  
  if ($_GET['mode'] == 'pending' || $_GET['mode'] == 'submitted') {
    
  	// Connect to databse (swiped from http://us4.php.net/function.mysql-connect)
		$link = mysql_connect($email_alias_host, $email_alias_username, $email_alias_password);
		if (!$link) {
			// Error
		    die('Could not connect to '.$email_alias_database.' database: ' . mysql_error());
		} else {
			// Select database and prepare query
			mysql_select_db($email_alias_database);
			$query = "SELECT * FROM ".$email_alias_table." WHERE (status = 'req' OR status = 'hld')";

			// Sort the query
			if(isset($_GET['sort'])) {
				$sort = $_GET['sort'];
			} else {
				$sort = "requestDate"; // Default to requestDate, ASC
			}
			if(isset($_GET['order']) && $_GET['order'] == "DESC") {
				$order = "DESC";
			} else {
				$order = "ASC"; // Default to requestDate, ASC
			}
			$query .= " ORDER BY ".$sort." ".$order;
			$result = mysql_query($query); // Run query
			
			$count = mysql_num_rows($result);
			
			$debug .= "You ran the following query on ".$email_alias_host.":<blockquote><small>".$query."</small></blockquote>";
			if ($show_debug) { echo $debug; } // set $show_debug in config.php
		
			echo '<h1>Pending Requests</h1>
	  	<br />There are '.$count.' requests in the queue.<br /><br />';
	  	
?>


<?php
// altered by steve for the reason version of the directory
// The original was not php code but straight html and did not add the name
// variable

        echo '<form name="PendingRequestForm" method="POST" action="admin.php?mode=submitted&name='.$_SESSION['user'].'">
	  	<table width="550" border="0">
			<tr bgcolor=#dddddd>';
?>
<?php

			// Date Requested
			if($sort == "requestDate") {
				if($order == "DESC") {
					$neworder = "ASC";
					$image = "&nbsp;<img src='up.png' border='0'>";
				} else {
					$neworder = "DESC";
					$image = "&nbsp;<img src='down.png' border='0'>";
				}
			} else {
				$image = "";
			}
			echo '<td height="25" valign="middle" width="35%"><div align="left"><font size="-1"><strong>
			<a href="admin.php?mode=pending&sort=requestDate&order='.$neworder.'">Date Requested'.$image.'</a></strong></font></div></td>';
			
			// Username
			if($sort == "username") {
				if($order == "DESC") {
					$neworder = "ASC";
					$image = "&nbsp;<img src='up.png' border='0'>";
				} else {
					$neworder = "DESC";
					$image = "&nbsp;<img src='down.png' border='0'>";
				}
			} else {
				$image = "";
			}
			echo '<td valign="middle" width="15%"><div align="left"><font size="-1"><strong>
			<a href="admin.php?mode=pending&sort=username&order='.$neworder.'">Username'.$image.'</a></strong></font></div></td>';
			
			// Request (Alias)
			if($sort == "alias") {
				if($order == "DESC") {
					$neworder = "ASC";
					$image = "&nbsp;<img src='up.png' border='0'>";
				} else {
					$neworder = "DESC";
					$image = "&nbsp;<img src='down.png' border='0'>";
				}
			} else {
				$image = "";
			}
			echo '<td valign="middle" width="30%"><font size="-1"><strong>
			<a href="admin.php?mode=pending&sort=alias&order='.$neworder.'">Request'.$image.'</a></strong></font></td>';
?>
			<td valign="middle" width="2%"><div align="center"><font size="-1"><img src="admin/red.png" title="Deny" alt="Deny" width="13" height="13" border="0"></font></div></td>
			<td valign="middle" width="2%"><div align="center"><font size="-1"><img src="admin/yellow.png" title="Hold" alt="Hold" width="13" height="13" border="0"></font></div></td>
			<td valign="middle" width="2%"><div align="center"><strong><img src="admin/green.png" title="Approve" alt="Approve" width="13" height="13" border="0"></strong></div></td>
			<td valign="middle" width="15%"><div align="center"><font size="-1"><strong>Notes</strong></font></div></td>
			</tr>
<?php

			// Do the row loop thing
			$i = 0;
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			  if ($i % 2 == 0) { echo '<tr>'; } else { echo '<tr bgcolor=#eeeeee>'; }
				echo '<td><div align="left"><font size="-1">'
				  .date($date_format,strtotime($row['requestDate'])).'</font></div></td>';
				echo '<td><div align="left"><font size="-1"><a href="user.php?name='.$row['username'].'">'
				  .$row['username'].'</a></font></div></td>';
				echo '<td><font size="-1">'
				  .$row['alias'].'</font></td>';
				echo '<td><div align="center"><font size="-1"><input type="radio" name="status_'
				  .$i.'" value="den"></font></div></div></td>';
				echo '<td><div align="center"><font size="-1"><input type="radio" name="status_'
				  .$i.'" value="hld"';
				if ($row['status'] == 'hld') {
					echo ' checked'; // if status is 'hld', the yellow button is checked
				}
				echo '></font></div></td>';
				echo '<td><div align="center"><font size="-1"><input type="radio" name="status_'
				  .$i.'" value="app"></font></div></td>'; // green
				echo '<td> <div align="center"><font size="-1">'
				  .'<input type="hidden" name="requestID_'.$i.'" value="'.$row['requestID'].'"'
				  .'<input type="text" name="notes_'.$i.'" value="'.$row['note'].'"></font></div></td>'; // Include existing notes
				echo '</tr>'; // end row
				$i = $i + 1;
			}
			
			mysql_close($link);
		}
  	
?>

		</table>
		<br /><br />
		<div align="center">
			<input type="submit" name="Submit" value="Submit">
		</div>
		</form>
		<div align="center"><font size="-2">Approval and denial notices will be sent to the user immediately.</font></div>
<?php
  }
  
  /********************************************
	  Screen to display COMPLETED alias requests  
   ********************************************/

  if ($_GET['mode'] == 'completed') {
  	
  	// Connect to databse (swiped from http://us4.php.net/function.mysql-connect)
		$link = mysql_connect($email_alias_host, $email_alias_username, $email_alias_password);
		if (!$link) {
			// Error
		    die('Could not connect to '.$email_alias_database.' database: ' . mysql_error());
		} else {
		  // Select database and run query
		  mysql_select_db($email_alias_database);

			// Get the query ready
			$query = "SELECT * FROM ".$email_alias_table." WHERE (status = 'app' OR status = 'den')";
			
			// If a search term was entered, limit query to search
			if (isset($_GET['search']) && $_GET['search'] != '')	{	
			  // If search terms exist, add them to the end of the query
			  $query .= " AND (username LIKE '%".$_GET['search']."%' OR alias LIKE '%".$_GET['search']."%' OR reviewer LIKE '%".$_GET['search']."%' OR note LIKE '%".$_GET['search']."%')";
			}
						
			// Sort the query
			if(isset($_GET['sort'])) {
				$sort = $_GET['sort'];
			} else {
				$sort = "statusDate"; // Default to statusDate, DESC
			}
			if(isset($_GET['order']) && $_GET['order'] == "ASC") {
				$order = "ASC";
			} else {
				$order = "DESC"; // Default to statusDate, DESC
			}
			$query .= " ORDER BY ".$sort." ".$order; // set order and sort
			
			// RUN THE QUERY AND COUNT RESULTS
			$result = mysql_query($query);
			// Count = How many requests are being displayed now
			$count = mysql_num_rows($result);
			// Total = How many total requests there are
			$total_array = mysql_fetch_row(mysql_query("SELECT count(*) from ".$email_alias_table." WHERE (status = 'app' OR status = 'den')"));
			$total = $total_array[0];
			
			// If a page number is set, pass to $page
			if (isset($_GET['page']) && $_GET['page'] > 0) {
			  $page = $_GET['page'];
			} else $page = 1;
			
			$totalpages = ceil($count / $requests_per_page);
			$prevpage = $page - 1;
			$nextpage = $page + 1;
			$pagestart = $requests_per_page * $prevpage;

			$debug .= "You ran the following query on ".$email_alias_host.":<blockquote><small>".$query."</small></blockquote>";
			if ($show_debug) { echo $debug; } // set $show_debug in config.php

?>
  	<h1>Completed Requests</h1>
  	<br />
  	<table width="100%" border="0" cellspacing="2" cellpadding="2">
		<tr> 
			<td width="65%">
  			<div align="left"><font size="-1">
    			<form name="Search" method=get action='admin.php'>
<?php
          echo '<input type="text" name="search" value="'.$_GET['search'].'">';
          echo '<input type="hidden" name="name" value="'.$_SESSION['user'].'">';
?>



      			<input type="hidden" name="mode" value="completed">
      			<input type="submit" value="Search">
    			</form>
  			</font></div>
			</td>
			<td width="35%">
			  <form name="Pagination" method=get action='admin.php'>
			    <div align="right">
<?php

    echo '<input type="hidden" name="mode" value="completed">';
    echo '<input type="hidden" name="name" value="'.$_SESSION['user'].'">';
    echo '<input type="hidden" name="search" value="'.$_GET['search'].'">';

    
    // If we've got more requests than will fit on the page, display pagination
    if ($count > $requests_per_page) {
      // Back arrow
		  if ($page > 1)
		    echo '<a href="admin.php?mode=completed&page='
		    .$prevpage.'&search='.$_GET['search'].'&name='.$_SESSION['user'].'">&lt;&lt;</a>';
		    else echo '&nbsp;&nbsp;&nbsp;&nbsp;';
		  echo '&nbsp;&nbsp;&nbsp;';
		  // Page __ of __
		  echo 'Page <input name="page" type="text" value="'.$page
		       .'" size="2" maxlength="4"> of '.$totalpages;
      echo '&nbsp;&nbsp;&nbsp;';
      // Forward arrow
      if ($page < $totalpages)
         echo '<a href="admin.php?mode=completed&page='
		    .$nextpage.'&search='.$_GET['search'].'&name='.$_SESSION['user'].'">&gt;&gt;</a>';
		    else echo '&nbsp;&nbsp;&nbsp;&nbsp;';
      
      // Limit the query and run it again
      $query .= " LIMIT ".$pagestart.", ".$requests_per_page;
      $result = mysql_query($query);
      $count = mysql_num_rows($result);
    }
?>
          </div>
        </form>
			</td>
    </tr>
	</table>

    <table width="550" border="0">
		<tr bgcolor=#dddddd>
<?php
		
			// Date Reviewed
			if($sort == "statusDate") {
				if($order == "DESC") {
					$neworder = "ASC";
					$image = "&nbsp;<img src='up.png' border='0'>";
				} else {
					$neworder = "DESC";
					$image = "&nbsp;<img src='down.png' border='0'>";
				}
			} else {
				$image = "";
			}
			print('<td height="25" valign="middle" width="35%"><div align="left"><font size="-1"><strong>
			<a href="admin.php?search='.$_GET['search'].'&mode=completed&name='.$_SESSION['user'].'&sort=statusDate&order='.$neworder.'">Date Reviewed'.$image.'</a></strong></font></div></td>');

			// Username
			if($sort == "username") {
				if($order == "DESC") {
					$neworder = "ASC";
					$image = "&nbsp;<img src='up.png' border='0'>";
				} else {
					$neworder = "DESC";
					$image = "&nbsp;<img src='down.png' border='0'>";
				}
			} else {
				$image = "";
			}
			echo '<td valign="middle" width="20%"><div align="left"><font size="-1"><strong>
			<a href="admin.php?search='.$_GET['search'].'&mode=completed&name='.$_SESSION['user'].'&sort=username&order='.$neworder.'">Username'.$image.'</a></strong></font></div></td>';

			// Request
			if($sort == "alias") {
				if($order == "DESC") {
					$neworder = "ASC";
					$image = "&nbsp;<img src='up.png' border='0'>";
				} else {
					$neworder = "DESC";
					$image = "&nbsp;<img src='down.png' border='0'>";
				}
			} else {
				$image = "";
			}
			echo '<td valign="middle" width="25%"><div><font size="-1"><strong>
			<a href="admin.php?search='.$_GET['search'].'&mode=completed&name='.$_SESSION['user'].'&sort=alias&order='.$neworder.'">Request'.$image.'</a></strong></font></div></td>';

			// Status
			if($sort == "status") {
				if($order == "DESC") {
					$neworder = "ASC";
					$image = "&nbsp;<img src='up.png' border='0'>";
				} else {
					$neworder = "DESC";
					$image = "&nbsp;<img src='down.png' border='0'>";
				}
			} else {
				$image = "";
			}
			echo '<td valign="middle" width="10%"><div align="center"><font size="-1"><strong>
			<a href="admin.php?search='.$_GET['search'].'&mode=completed&name='.$_SESSION['user'].'&sort=status&order='.$neworder.'">Status'.$image.'</a></strong></font></div></td>';

			// Reviewer
			if($sort == "reviewer") {
				if($order == "DESC") {
					$neworder = "ASC";
					$image = "&nbsp;<img src='up.png' border='0'>";
				} else {
					$neworder = "DESC";
					$image = "&nbsp;<img src='down.png' border='0'>";
				}
			} else {
				$image = "";
			}
			echo '<td valign="middle" width="10%"><div align="left"><font size="-1"><strong>
			<a href="admin.php?search='.$_GET['search'].'&mode=completed&name='.$_SESSION['user'].'&sort=reviewer&order='.$neworder.'">Reviewer'.$image.'</a></strong></font></div></td>';
			
?>
			<td valign="middle" width="5%">&nbsp;</td>
		</tr>
<?php
		
			// Do the row loop thing
			$i = 0; $avgSum = 0;
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				if ($i % 2 == 0) { echo '<tr>'; } else { echo '<tr bgcolor=#eeeeee>'; }
				printf('<td><div align="left"><font size="-1">
					%s</font></div></td>', date($date_format,strtotime($row['statusDate'])));
				printf('<td><div align="left"><font size="-1">
					<a href="user.php?name=%s">%s</a></font></div></td>', $row['username'], $row['username']);
				printf('<td><font size="-1">
					%s</font></td>', $row['alias']);
				echo '<td><div align="center">';
				if ($row['status'] == 'app') {
					echo '<img src="admin/green.png" title="Approved" border="0">';
				}
				if ($row['status'] == 'den') {
					echo '<img src="admin/red.png" title="Denied" border="0">';
				}
				echo '</div></td>';
				echo '<td><div align="left"><font size="-1">'.$row['reviewer'].'</font></div></td>';
				
				$notes_url = "'admin/notes.php?requestID=".$row['requestID']."'";
				echo '<td><div align="center"><a href="javascript:popUp(\'admin/notes.php?requestID='
				     .$row["requestID"].'\',\'Notes\',\'height=250,width=400,resizable=1\');">';
				if ($row['note'] == '')
					echo '<img src="admin/note_0.png" border="0"></a>';
				else
				  echo '<img src="admin/note_1.png" title="'.$row["note"].'" border="0"></a>';
				
				echo '</div></td></tr>'; // end row
				$avgSum = $avgSum + strtotime($row['statusDate']) - strtotime($row['requestDate']);
				$i = $i + 1;
			}
			mysql_close($link);
			
			// Calculate average queue time for displayed requests
			if ($count != 0) {
			 $queueTime = $avgSum / $count;
			} else {
			  $queueTime = 0;
			}
			
?>
</table><br />
<?php
      echo '<div align="right"><small>Requests shown: '.$count.' ('.$total.' total)<br />
			  Average queue time: '.getDateString($queueTime,0).'</small></div>';
	}
}
@ldap_unbind($ds);

// Luther template footer.
require_once("./foot.html");

?>
