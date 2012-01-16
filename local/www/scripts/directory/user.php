<?php
require_once("./functions.php");
require_once("./config.php");

// added by Steve Smith
// for using this script with the Reason directory

// $http_cookie = $_SERVER['HTTP_COOKIE'];
// echo($http_cookie);
// if (stripos($http_cookie, "REASON_SESSION")){
//     echo "<hr></hr>";
//     echo "cookie = " . stripos($http_cookie, "REASON_SESSION_EXISTS");
// }
// 
// echo "<hr></hr>";
// echo "referer" . $_SERVER['HTTP_REFERER'];
//     echo "refererpos=" . stripos($_SERVER['HTTP_REFERER'],"reasondev.luther.edu/newdirectory/");
// if (stripos($_SERVER['HTTP_REFERER'], "reasondev.luther.edu/newdirectory/")){
// 
//     echo "<hr></hr>";
//     echo "refererpos=" . stripos($_SERVER['HTTP_REFERER'],"reasondev.luther.edu/newdirectory/");
// }

print_r($_SESSION);
// if ($_SERVER['HTTP_COOKIE']['REASON_SESSION_EXISTS'] && stripos('https://reasondev.luther.edu/newdirectory/', $_SERVER['HTTP_REFERER']) !== false){
if (stripos($_SERVER['HTTP_COOKIE'], "REASON_SESSION") && (stripos($_SERVER['HTTP_REFERER'], "reasondev.luther.edu/newdirectory/"))){
	$_SESSION['logged_in'] = 1;
	$_SESSION['not_anon'] = 1;
	$_SESSION['user'] = $_GET['name'];	
}

if (isset($_SESSION['logged_in']) && isset($_POST['update'])) {
  $ds = @ldap_connect($ldap_server, $ldap_port);

  if (!$ds) {
    require_once('./head.html');
    print "Unable to connect to the LDAP server";
    require_once("./foot.html");
    exit(1);
  }

  $res = @ldap_bind($ds, $_SESSION['userdn'], $_SESSION['pass']);
    
  if (!$res) {
    require_once('./head.html');
    print "Unable to bind to the LDAP server";
    require_once("./foot.html");
    exit(1);
  }

  $name = $_POST['name'];

  if (($name != $_SESSION['user']) &&
      !is_admin($ds, $_SESSION['user'])) {
    require_once('./head.html');
    print "You are not allowed in this area!";
    require_once("./foot.html");
    exit(1);
  }


  $attr = array("uid",
                "milterForwardAddress",
                "milterForwardLocalCopy",
                "mailLocalAddress",
                "mailRoutingAddress",
                "selfAllowedAttributes",
                "mail",
                "vacationFlag",
                "vacationSubject",
                "vacationMessage");


  if ($_SESSION['logged_in'] && 
      (is_admin($ds, $_SESSION['user']) || ($name == $_SESSION['user']))) {
    $filter = "(uid=$name)";
  } else {
    $filter = "(& (uid=$name)(!(privacyFlag=*)))";
  }

  $result = @ldap_search($ds, $peopledn, $filter, $attr);
  $user = @ldap_get_entries($ds, $result);

  $bad_emails = array ();
  $self_emails = array ();
  $csv_emails;
  $emails;
  $duplicate_email = ""; 
  $duplicate_request = ""; 
  $invalid_requested_email = false;

  if ($user['count'] > 0) {
    if ($_POST['emailforward'] != '') {
      $csv_emails = preg_replace("/\s/", "", $_POST['emailforward']);
      $emails = explode(",", $csv_emails);

      $j = 0;

      for ($i = 0; $i < count($emails); $i++) {
        $email = "";

        // can't forward to self
        if (!strcasecmp($emails[$i],$user[0]['mail'][0]) ||
            !strcasecmp($emails[$i],$user[0]['uid'][0])) {
          array_push($self_emails, $emails[$i]);
        }
        // don't allow forwarding to an alias
        for ($k = 0; $k < count($user[0]['maillocaladdress']) - 1; $k++) {
          if (!strcasecmp($emails[$i], $user[0]['maillocaladdress'][$k])
            && !in_array($emails[$i], $self_emails)) {
            array_push($self_emails, $emails[$i]);
          }
        }
        // no forwarding to a requested alias with luther.edu domain
        preg_match("/@luther.edu/", $emails[$i], $s);
        if ($s[0] == "@luther.edu") {
          if ((($conn = mysql_connect($email_alias_host, $email_alias_username, $email_alias_password))
            && (mysql_select_db($email_alias_database, $conn))) == FALSE) {
              // error
          } else {
            preg_match("/[^@]+/", $emails[$i], $s);
            $aUser = $user[0]['uid'][0];
            $sql = "SELECT alias FROM aliasRequest " .
              "WHERE '$s[0]' = alias";
            $dbResult = mysql_query($sql, $conn);
            while ($row = mysql_fetch_array($dbResult, MYSQL_ASSOC)) {
              if (!in_array($emails[$i], $self_emails)) {
                array_push($self_emails, $emails[$i]);
              }
              break;
            }
          }
          // requested e-mail forwarding address and email alias can't match
          preg_match("/[^@]+/", $_POST['vsecondary'], $s);
          if ($emails[$i] == $s[0] . "@luther.edu") {
            if (!in_array($emails[$i], $self_emails)) {
              array_push($self_emails, $emails[$i]);
            }
          }
        }

        if (count($self_emails) == 0) {
          preg_match("/(^[\w-]+([.][\w_-]+){0,4}[@][\w_-]+([.][\w-]+){1,3}$)/", $emails[$i], $match);

          $email = $match[1];

          if ($email && ($email != "")) {
            $entry['milterForwardAddress'][$j] = $email;
            $j++;
          } else {
            array_push($bad_emails, $emails[$i]);
          }
        }
      }
    } else {
      $entry['milterForwardAddress'] = array();
    }

    if (is_array($user[0]['milterforwardaddress'])) {
      @ldap_mod_replace($ds, "uid=$name,$peopledn", $entry);
    } else {
      if (count($entry['milterForwardAddress'])) {
        @ldap_mod_add($ds, "uid=$name,$peopledn", $entry);
      }
    }

    unset($entry);

    if ($_POST['cflag'] == "yes") {
      $entry['milterForwardLocalCopy'] = "yes";
    } else {
      $entry['milterForwardLocalCopy'] = array();
    }

    if (is_array($user[0]['milterforwardlocalcopy'])) {
      @ldap_mod_replace($ds, "uid=$name,$peopledn", $entry);  
    } else {
      if (count($entry['milterForwardLocalCopy'])) {
        @ldap_mod_add($ds, "uid=$name,$peopledn", $entry);
      }
    }

    unset($entry);

    // email alias request
    if ($_POST['vsecondary'] != '') {
      // strip off chars to the right of any @ if it's present
      //preg_match("/[^@]+/", $_POST['vsecondary'], $s);
      // match A-Z, a-z, 0-9, and _ (2 to 30 chars)
      preg_match("/^[\w.-]{2,30}/", $_POST['vsecondary'], $s);
      // don't allow .. -- or __ (must be separated by at least one character)
      preg_match("/^[a-zA-Z0-9]{1,}([_.-][a-zA-Z0-9]{1,})*/", $s[0], $match);
      //print $s[0]."<br>";
      //print $match[0]."<br>";
      if ($_POST['vsecondary'] == $match[0]) {
      //if (preg_match("/(^[\w-]+([.][\w-]+){0,4})/", $_POST['vsecondary'], $match)) {
        //print "match = ";
        //print $match[0];

        // check ldap for existing emails
        if (is_unique_email($ds, $match[0]."@luther.edu")) {
          // connect to database
          // $email_alias_host and $email_alias_database are set in ../config.php
          if ((($conn = mysql_connect($email_alias_host, $email_alias_username, $email_alias_password))
             && (mysql_select_db($email_alias_database, $conn))) == FALSE) {
               // error
          } else {
            // make certain the alias is not currently requested 
            $aUser = $user[0]['uid'][0];
            //$sql = "SELECT alias FROM aliasRequest " .
            //  "WHERE '$match[0]' = alias";
            $sql = "SELECT alias FROM aliasRequest " .
              "WHERE '$match[0]' = alias AND (status = 'req' OR status = 'hld')";
            $dbResult = mysql_query($sql, $conn);
            $dupEntry = false;
            while ($row = mysql_fetch_array($dbResult, MYSQL_ASSOC)) {
              $dupEntry = true;
              break;
            }
            if ($dupEntry == false) {
              // enter request in database     
              $sql = "INSERT INTO aliasRequest (username, alias, requestDate, status) " .
                "VALUES ('$name', '$match[0]', now(), 'req')";
              if (!mysql_query($sql, $conn)) {
                // error 
              }
            } else {
                $duplicate_request = $match[0]."@luther.edu";
            }
            
          }
        } else {
            $duplicate_email = $match[0]."@luther.edu";
        }
      } else {
          $invalid_requested_email = true;
      }
    }

    unset($entry);

    if ($_POST['vmessage'] != '') {
      $entry['vacationMessage'] = stripslashes($_POST['vmessage']);
    } else {
      $entry['vacationMessage'] = array();
    }

    if (is_array($user[0]['vacationmessage'])) {
      @ldap_mod_replace($ds, "uid=$name,$peopledn", $entry);
    } else {
      if (count($entry['vacationMessage'])) {
        @ldap_mod_add($ds, "uid=$name,$peopledn", $entry);
      }
    }

    unset($entry);
    
    if ($_POST['vsubject'] != '') {
      $entry['vacationSubject'] = stripslashes($_POST['vsubject']);
    } else {
      $entry['vacationSubject'] = array();
    }

    if (is_array($user[0]['vacationsubject'])) {
      @ldap_mod_replace($ds, "uid=$name,$peopledn", $entry);
    } else {
      if (count($entry['vacationSubject'])) {
        @ldap_mod_add($ds, "uid=$name,$peopledn", $entry);
      }
    }

    unset($entry);

    if ($_POST['vflag'] == "yes") {
      $entry['vacationFlag'] = "yes";
    } else {
      $entry['vacationFlag'] = array();
    }

    if (is_array($user[0]['vacationflag'])) {
      @ldap_mod_replace($ds, "uid=$name,$peopledn", $entry);  
    } else {
      if (count($entry['vacationFlag'])) {
        @ldap_mod_add($ds, "uid=$name,$peopledn", $entry);
      }
    }

    unset($entry);

    // remove any email aliases that were slated for deletion 
    // and change appropriate $_POST['mailLocalAddressVis']
    $i = 0;
    for ($j = 0; $j < count($user[0]['maillocaladdress']) - 1; $j++) {
      $aliasVis = "mailLocalAddressVis".$j;

	if ($_POST[$aliasVis] == 99) {


	#print $user[0]['maillocaladdress'][$j] . " --- should be deleted.";



	    // try to call google to add nickname ------------------------------------------------------------------------

		$migrateusername = $name;
		$removenickname = $user[0]['maillocaladdress'][$j];

		#print "Norse Mail trying to remove $removenickname<br>";
		#die;

		$data = array ('migrateusername' => $migrateusername, 'removenickname' => $removenickname, 'key' => 'cow9999');
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

		$fp = fopen('https://webmail.luther.edu/src/googleconvert_delnickname.php', 'r', false, $context);

		$contents = stream_get_contents($fp);

		print $contents;




	     // end google code ------------------------------------------------------------------------



	}


	

      if ($_POST[$aliasVis] != 99) {
        $entry['mailLocalAddress'][$i] = $user[0]['maillocaladdress'][$j];
        $aliasVisi = "mailLocalAddressVis".$i;
        $_POST[$aliasVisi] = $_POST[$aliasVis];
        $i++;
      } else if ($_POST['vprimary'] == $user[0]['maillocaladdress'][$j]) {
        $_POST['vprimary'] = $user[0]['maillocaladdress'][0];
      }
    }

    if (is_array($user[0]['maillocaladdress'])) {
      @ldap_mod_replace($ds, "uid=$name,$peopledn", $entry);  
    } else {
      if (count($entry['mailLocalAddress'])) {
        @ldap_mod_add($ds, "uid=$name,$peopledn", $entry);
      }
    }

    unset($entry);

    // get result with removals
    if ($i != $j) {
      $result = @ldap_search($ds, $peopledn, $filter, $attr);
      $user = @ldap_get_entries($ds, $result);
    }

    // has primary mail address changed?
    if ($_POST['vprimary'] != ''
      && $_POST['vprimary'] != $user[0]['mail'][0]) {
      $_SESSION['primary_email_changed'] = true;
    }
    // is primary mail address hidden?
    else if (count($user[0]['maillocaladdress']) - 1 > 1) {
      for ($j = 0; $j < count($user[0]['maillocaladdress']) - 1; $j++) {
        $aliasVis = "mailLocalAddressVis".$j;
        if ($user[0]['maillocaladdress'][$j] == $_POST['vprimary']
          && $_POST[$aliasVis] == 0) {
          $_SESSION['primary_email_hidden'] = true;
          break;
        }
      }
    }

    // set primary mail address
    if ($_POST['vprimary']) {
      if (in_array($_POST['vprimary'], $user[0]['maillocaladdress'])) {
        $entry['mail'] = $_POST['vprimary'];
      } else {
        $entry['mail'] = $user[0]['maillocaladdress'][0];
      }


      if($_SESSION['primary_email_changed']) {
          $new_dn = "uid=$name,$peopledn"; // adapt for alumni case
          $new_email = $entry['mail'];
          $sql = "insert into $primary_email_update_table (userdn, new_email, requested) values('$new_dn', '$new_email', now())";
          $conn = mysql_connect($email_alias_host, $email_alias_username, 
                                $email_alias_password);
          mysql_select_db($email_alias_database, $conn);
          $dbResult = mysql_query($sql, $conn);
          if(!$dbResult) {
              $sql = "update $primary_email_update_table set new_email='$new_email', requested=now() where userdn='$new_dn'";
              $dbResult = mysql_query($sql, $conn);              
          }
      } else {
          # no change to primary email
      }

      if (is_array($user[0]['mail'])) {
        @ldap_mod_replace($ds, "uid=$name,$peopledn", $entry);
        // 10 Nov 2005 - BJ returning FALSE - primary mail not being set
      } else {
        if (count($entry['mail'])) {
          @ldap_mod_add($ds, "uid=$name,$peopledn", $entry);
        }
      }

      unset($entry);
    }

    // set mailRoutingAddress to luther assigned email
    //if (count($user[0]['maillocaladdress']) - 1 > 1) {
      //$entry['mailRoutingAddress'] = $user[0]['maillocaladdress'][0];
      $entry['mailRoutingAddress'] = $name . "@luther.edu";
      if (is_array($user[0]['mailroutingaddress'])) {
        @ldap_mod_replace($ds, "uid=$name,$peopledn", $entry);
      } else {
        //if (count($entry['mailRoutingAddress'])) {
          @ldap_mod_add($ds, "uid=$name,$peopledn", $entry);
        //}
      }

      unset($entry);

    //}

    $self_allowed_attrs = array ("postalAddress",
                                 "l",
                                 "st",
                                 "lutherC",
                                 "postalCode",
                                 "telephoneNumber",
                                 "mobile",
                                 "spouseName",
                                 "childName",
                                 "ocPostalAddress",
                                 "ocL",
                                 "ocSt",
                                 "ocC",
                                 "ocPostalCode",
                                 "ocPhone");

    $old_attrs = explode(":", $user[0]['selfallowedattributes'][0]);

    $entry['selfAllowedAttributes'] = array();

    foreach ($old_attrs as $k => $v) {
      if (strlen($old_attrs[$k]) && !in_array($v, $self_allowed_attrs)
        && (strncmp($v, "mailLocalAddressVis", 19 ) != 0)) {
        $entry['selfAllowedAttributes'][0] .= ":$v";
      }
    }

    if ($_POST['postalAddress']) {
      $entry['selfAllowedAttributes'][0] .= ":postalAddress:l:st:lutherC:postalCode";
    }

    if ($_POST['telephoneNumber']) {
      $entry['selfAllowedAttributes'][0] .= ":telephoneNumber";
    }

    if ($_POST['mobile']) {
      $entry['selfAllowedAttributes'][0] .= ":mobile";
    }

    if ($_POST['spouseName']) {
      $entry['selfAllowedAttributes'][0] .= ":spouseName";
    }
    
    if ($_POST['childName']) {
      $entry['selfAllowedAttributes'][0] .= ":childName";
    }
    
    if ($_POST['ocPostalAddress']) {
      $entry['selfAllowedAttributes'][0] .= ":ocPostalAddress:ocL:ocSt:ocC:ocPostalCode";
    }

    if ($_POST['ocPhone']) {
      $entry['selfAllowedAttributes'][0] .= ":ocPhone";
    }

    if (count($user[0]['maillocaladdress']) - 1 <= 1) {
    // no aliases exist
      $entry['selfAllowedAttributes'][0] .= ":mailLocalAddressVis0";
    } else {
      for ($j = 0; $j < count($user[0]['maillocaladdress']) - 1; $j++) {
        $aliasVis = "mailLocalAddressVis".$j;
        if ($_POST[$aliasVis] == 1 
          || $_POST['vprimary'] == $user[0]['maillocaladdress'][$j])  {
          $entry['selfAllowedAttributes'][0] .= ":".$aliasVis;
        }
      }
    }

    if ($entry['selfAllowedAttributes'][0] != "") {
      $entry['selfAllowedAttributes'][0] .= ":";
    }
    
    if (is_array($user[0]['selfallowedattributes'])) {
      @ldap_mod_replace($ds, "uid=$name,$peopledn", $entry);
    } else {
      if (count($entry['selfAllowedAttributes'])) {
        @ldap_modify($ds, "uid=$name,$peopledn", $entry);
      }
    }

    @ldap_unbind($ds);

    if (count($bad_emails) || count($self_emails)
      || $duplicate_email != ""
      || $duplicate_request != ""
      || $invalid_requested_email == true) {
      $_GET['mode'] = 'edit';
      $_GET['name'] = $_POST['name'];
    } else {
      redirect("?netid[]=".$_POST['name']);
    }
  }
} 

require_once("./head.html");

// page that allows user to edit info (i.e. email forwarding, vacation,
// show or hide attributes, ...)
if (isset($_GET['mode']) && ($_GET['mode'] == 'edit')) {
  $ds = @ldap_connect($ldap_server, $ldap_port);

  if (!$ds) {
    print "Unable to connect to the LDAP server";
    require_once("./foot.html");
    exit(1);
  }

  $res = @ldap_bind($ds, $_SESSION['userdn'], $_SESSION['pass']);

  if (!$res) {
    print "Unable to bind to the LDAP server";
    require_once("./foot.html");
    exit(1);
  }

  $name = urldecode($_GET['name']);

  $attr = array("uid",
                "cn",
                "mail",
                "eduPersonAffiliation",
                "milterForwardAddress",
                "milterForwardLocalCopy",
                "mailLocalAddress",
                "selfAllowedAttributes");

  if ($_SESSION['logged_in'] && 
      (is_admin($ds, $_SESSION['user']) || ($name == $_SESSION['user']))) {
    $filter = "(uid=$name)";
  } else {
    $filter = "(& (uid=$name)(!(privacyFlag=*)))";
  }

  $result = @ldap_search($ds, $peopledn, $filter, $attr);
  $user = @ldap_get_entries($ds, $result);

  if ($user['count'] > 0) {
    if (($user[0]['uid'][0] != $_SESSION['user']) &&
        !is_admin($ds, $_SESSION['user'])) {
      print "You are not allowed in this area!";
      require_once("./foot.html");
      exit(1);
    }

    $attrs = split(":", $user[0]['selfallowedattributes'][0]);

    // Chop off the first and last (empty) members
    array_pop($attrs);
    array_shift($attrs);
    
    require_once("./javascript.js");

    if($debug && $linuxbox_vm) {
        $user_form_action = "user.php";
    } else {
//         $user_form_action = $_SERVER['PHP_SELF'];
        $user_form_action = "user.php?mode=edit&name="
         .urlencode($name);
    }

?>
    <form name="MyForm" method=post action='<?=$user_form_action?>'>
    <table border=0 cellpadding=3 cellspacing=0 align=center>
      <tr><td colspan=2 align=center>Editing entry for <b><?=$user[0]['cn'][0]?></b></td></tr>
      <tr><td colspan=2>&nbsp;</td></tr>
<?php     
      if (count($self_emails)) {
        echo "<tr><td colspan=2><font color=red>";
        forward_to_self_error($self_emails,count($emails));
        echo "</font><br><br></td></tr>";
      }

      if (count($bad_emails)) {
        echo "<tr><td colspan=2><font color=red>";
        invalid_email_error($_GET['name'], $bad_emails);
        echo "</font><br><br></td></tr>";
      }
      
      if ($invalid_requested_email == true) {
        echo "<tr><td colspan=2><font color=red>";
        invalid_requested_email_error();
        echo "</font><br><br></td></tr>";
      }
      
      if ($duplicate_email != "") {
        echo "<tr><td colspan=2><font color=red>";
        duplicate_email_error($duplicate_email);
        echo "</font><br><br></td></tr>";
      }

      if ($duplicate_request != "") {
        echo "<tr><td colspan=2><font color=red>";
        duplicate_request_error($duplicate_request);
        echo "</font><br><br></td></tr>";
      }
?>


<?php
// if for google users 


if ( $user[0]['mailhost'][0]<>"aspmx.l.google.com" ) { 

?>


<? } // end if for google users   ?>





      <tr bgcolor=#eeeeee><td colspan=2 align=center><b>Request Additional E-mail Address</b></td></tr>
      <tr valign=top>
        <td nowrap>
<?php
          if ($duplicate_email != "" || $duplicate_request != "" || $invalid_requested_email == true) {
            print "<font color=red><b>Request</b></font>";
          } else {
            print "Request";
          }
          echo "</td>";

          if ((($conn = mysql_connect($email_alias_host, $email_alias_username, $email_alias_password))
            && (mysql_select_db($email_alias_database, $conn))) == FALSE) {
            echo " <td>
                   (Unable to process at this time.  Please try again later).
                   <br /><br />
                   </td>";
          } else {
            echo " <td>
                   <input type=text name=vsecondary size=20> 
                   <font size=-1 color=blue>@luther.edu</font>
                   <br /><br />
                   </td>";
          }
      echo "</tr>";
?>

<?php
      if (user_has_affiliation ($ds, $user[0]['uid'][0], "Faculty") ||
          user_has_affiliation ($ds, $user[0]['uid'][0], "Staff") ||
          user_has_affiliation ($ds, $user[0]['uid'][0], "Emeritus") ||
          user_has_affiliation ($ds, $user[0]['uid'][0], "Student") ||
          user_has_affiliation ($ds, $user[0]['uid'][0], "Student - Not Enrolled this Term") ||
          user_has_affiliation ($ds, $user[0]['uid'][0], "Student - Planning to Enroll")) {
?>
        <tr align=center bgcolor=#eeeeee>
          <td colspan=3 nowrap><b>Attribute Visibility</b></td>
        </tr>
        <tr>
          <td colspan=3>
          Select which attributes you want to be visible to 
          authenticated users<br><br>
          </td>
        </tr>
    </table>
    <table border=0 cellpadding=3 cellspacing=0 align=center>

        <tr>
          <td nowrap>Home Address</td>
          <td>
<?php
          if (in_array("postalAddress", $attrs)) {
            print  "<input type=radio name=postalAddress value=1 checked> Visible
                    <input type=radio name=postalAddress value=0> Hidden";
          } else {
            print  "<input type=radio name=postalAddress value=1> Visible
                    <input type=radio name=postalAddress value=0 checked> Hidden";
          }
?>
          </td>
        </tr>
        <tr>
          <td nowrap>Home Phone</td>
          <td>
<?php
          if (in_array("telephoneNumber", $attrs)) {
            print "<input type=radio name=telephoneNumber value=1 checked> Visible
                   <input type=radio name=telephoneNumber value=0> Hidden";
          } else {
            print "<input type=radio name=telephoneNumber value=1> Visible
                   <input type=radio name=telephoneNumber value=0 checked> Hidden";
          }
?>
          </td>
        </tr>


<?php
      }

      if (user_has_affiliation ($ds, $user[0]['uid'][0], "Faculty") ||
          user_has_affiliation ($ds, $user[0]['uid'][0], "Staff") ||
          user_has_affiliation ($ds, $user[0]['uid'][0], "Emeritus")) {
?>

        <tr>
          <td nowrap>Mobile Phone</td>
          <td>
<?php
          if (in_array("mobile", $attrs)) {
            print "<input type=radio name=mobile value=1 checked> Visible
                   <input type=radio name=mobile value=0> Hidden";
          } else {
            print "<input type=radio name=mobile value=1> Visible
                   <input type=radio name=mobile value=0 checked> Hidden";
          }
?>
          </td>
        </tr>


        <tr>
          <td nowrap>Spouse Name</td>
          <td>
<?php
          if (in_array("spouseName", $attrs)) {
            print "<input type=radio name=spouseName value=1 checked> Visible
                   <input type=radio name=spouseName value=0> Hidden";
          } else {
            print "<input type=radio name=spouseName value=1> Visible
                   <input type=radio name=spouseName value=0 checked> Hidden";
          }
?>
          </td>
        </tr>
        <tr>
          <td nowrap>Child Name</td>
          <td>
<?php
          if (in_array("childName", $attrs)) {
            print "<input type=radio name=childName value=1 checked> Visible
                   <input type=radio name=childName value=0> Hidden";
          } else {
            print "<input type=radio name=childName value=1> Visible
                   <input type=radio name=childName value=0 checked> Hidden";
          }
?>
          </td>
        </tr>
<?php
      }

      if (user_has_affiliation ($ds, $user[0]['uid'][0], "Student") ||
          user_has_affiliation ($ds, $user[0]['uid'][0], "Student - Not Enrolled this Term") ||
          user_has_affiliation ($ds, $user[0]['uid'][0], "Student - Planning to Enroll")) {
?>
        <tr>
          <td nowrap>Off Campus Address</td>
          <td>
<?php
        if (in_array("ocPostalAddress", $attrs)) {
          print "<input type=radio name=ocPostalAddress value=1 checked> Visible
                 <input type=radio name=ocPostalAddress value=0> Hidden";
        } else {
          print "<input type=radio name=ocPostalAddress value=1> Visible
                 <input type=radio name=ocPostalAddress value=0 checked> Hidden";
        }
?>
          </td>
        </tr>
        <tr>
          <td nowrap>Off Campus Phone</td>
          <td>
<?php
        if (in_array("ocPhone", $attrs)) {
          print "<input type=radio name=ocPhone value=1 checked> Visible
                 <input type=radio name=ocPhone value=0> Hidden";
        } else {
          print "<input type=radio name=ocPhone value=1> Visible
                 <input type=radio name=ocPhone value=0 checked> Hidden";
        }
?>
          </td>
        </tr>
<?php
      }

      if (user_has_affiliation ($ds, $user[0]['uid'][0], "Faculty") ||
          user_has_affiliation ($ds, $user[0]['uid'][0], "Staff") ||
          user_has_affiliation ($ds, $user[0]['uid'][0], "Emeritus") ||
          user_has_affiliation ($ds, $user[0]['uid'][0], "Student") ||
          user_has_affiliation ($ds, $user[0]['uid'][0], "Student - Not Enrolled this Term") ||
          user_has_affiliation ($ds, $user[0]['uid'][0], "Student - Planning to Enroll") ) {
          //&& count($user[0]['maillocaladdress']) - 1 > 1 ) {  // user must have an email alias
?>
        <tr>
          <td nowrap><u>E-mail:</u></td>
        </tr>

<?php
  /*
    print "ds = " . $ds . "<br />";
    print "res = " . $res . "<br />";
    print "filter = " . $filter . "<br />";
    print "result = " . $result . "<br />";
    print "peopledn = " . $peopledn . "<br />";
    //foreach ($_SESSION as $k => $v) {
    //  printf ("SESSION: %s => %s", $k, $v);
    //  print "<br />";
    //  }
    foreach ($user[0] as $k => $v) {
      printf ("%s => %s", $k, $v);
      print "<br />";
      }
      print "<br />";
      print "name = " . $name . "<br />";
      print "mail[0] = " . $user[0]['mail'][0] . "<br />";
      print "vacationsubject[0] = " . $user[0]['vacationsubject'][0] . "<br />";
      print "edupersonaffiliation[0] = " . $user[0]['edupersonaffiliation'][0] . "<br />";
      print "selfallowedattributes[0] = " . $user[0]['selfallowedattributes'][0] . "<br />";
      print "privacyflag[0] = " . $user[0]['privacyflag'][0] . "<br />";
      print "cn[0] = " . $user[0]['cn'][0] . "<br />";
      print "cn[1] = " . $user[0]['cn'][1] . "<br />";
      print "maillocaladdress[0] = " . $user[0]['maillocaladdress'][0] . "<br />";
      print "maillocaladdress[1] = " . $user[0]['maillocaladdress'][1] . "<br />";
      print "maillocaladdress[2] = " . $user[0]['maillocaladdress'][2] . "<br />";
      print "maillocaladdress count = " . count($user[0]['maillocaladdress']) . "<br />";
      print "mail count = " . count($user[0]['mail']) . "<br />";
      print "cn count = " . count($user[0]['cn']) . "<br />";
      print "mailroutingaddress[0] = " . $user[0]['mailroutingaddress'][0] . "<br />";
      print "mailroutingaddress count = " . count($user[0]['mailroutingaddress']) . "<br />";
      */

        for ($j = 0; $j < count($user[0]['maillocaladdress']) - 1; $j++) {
?>
        <tr>
          <td>
          <i>
<?php
          echo $user[0]['maillocaladdress'][$j]; 
?>
          </i>
          </td>
          <td>
<?php
          $aliasVis = "mailLocalAddressVis".$j;
          if (in_array($aliasVis, $attrs)) {
            if ($j == 0 && count($user[0]['maillocaladdress']) - 1 > 1) {
              print "<input type=radio name='$aliasVis' value=1 checked> Visible
                     <input type=radio name='$aliasVis' value=0> Hidden";
              } else if (count($user[0]['maillocaladdress']) - 1 > 1) {
              print "<input type=radio name='$aliasVis' value=1 checked> Visible
                     <input type=radio name='$aliasVis' value=0> Hidden
                     <input type=radio name='$aliasVis' value=99> Remove";
            }
          } else {
            if ($j == 0 && count($user[0]['maillocaladdress']) - 1 > 1) {
              print "<input type=radio name='$aliasVis' value=1> Visible
                     <input type=radio name='$aliasVis' value=0 checked> Hidden";
            } else if (count($user[0]['maillocaladdress']) - 1 > 1) {
              print "<input type=radio name='$aliasVis' value=1> Visible
                     <input type=radio name='$aliasVis' value=0 checked> Hidden
                     <input type=radio name='$aliasVis' value=99> Remove";
            }
          }
?>
          </td>
        </tr>
<?php
        }
      }
?>
      <tr>
        <td nowrap>Primary:</td>
        <td>
        <select name="vprimary">
<?php
        // either username or an email alias can be the primary mail address
        for ($j = 0; $j < count($user[0]['maillocaladdress']) - 1; $j++) {
          if ($user[0]['mail'][0] == $user[0]['maillocaladdress'][$j]) {
            print "<option selected value=" . $user[0]['maillocaladdress'][$j] . ">" . $user[0]['maillocaladdress'][$j] . "</option>";
          } else {
            print "<option value=" . $user[0]['maillocaladdress'][$j] . ">" . $user[0]['maillocaladdress'][$j] . "</option>";
          }
        }
?>
        </select>
        </td>
      </tr>

<?php
        // connect to database
        // $email_alias_host and $email_alias_database are set in ../config.php
        if ((($conn = mysql_connect($email_alias_host, $email_alias_username, $email_alias_password))
           && (mysql_select_db($email_alias_database, $conn))) == FALSE) {
             // error
        } else {
          $aUser = $user[0]['uid'][0];
          $sql = "SELECT alias FROM aliasRequest " .
            "WHERE '$aUser' = username AND ('req' = status OR 'hld' = status)";
          $dbResult = mysql_query($sql, $conn);
          while ($row = mysql_fetch_array($dbResult, MYSQL_ASSOC)) {
            $aReq .= $row["alias"] . "@luther.edu, ";            
          }
        }

        if (strlen($aReq)) {
?>
      <tr>
        <td nowrap>Requested:<br><small>Allow up to 1 week<br>for review</small></td>
        <td>
<?php
          echo rtrim($aReq, ", "); 
?>
        </td>
      </tr>

<?php
        }
?>

      <tr>
        <td align=center colspan=2>
          <input type=hidden name=email value='<?=$user[0]['mail'][0]?>'>
          <br><input type=submit name=update value='Update Entry'
              onClick='return check_details(this.form);'>
        </td>
      </tr>
    </table>

    <input type=hidden name=name value=<?=$name?>>
    </form>
<?php
  }
  @ldap_unbind($ds);


  // page that displays info about user
  } else if (isset($_GET['name'])) {

  $ds = @ldap_connect($ldap_server, $ldap_port);

  if (!$ds) {
    print "Unable to connect to the LDAP server";
    require_once("./foot.html");
    exit(1);
  }

  $res = @ldap_bind($ds, $_SESSION['userdn'], $_SESSION['pass']);

  if (!$res) {
    print "Unable to bind to the LDAP server";
    require_once("./foot.html");
    exit(1);
  }

  $name = urldecode($_GET['name']);

  search_box();
?>
  <table align=center border=0 cellpadding=5 cellspacing=0>
<?php
  $attr = array ("givenname",
                 "sn", 
                 "cn",
                 "uid", 
                 "mail",
                 "milterForwardAddress",
                 "mailLocalAddress",
                 "mailRoutingAddress",
                 "selfAllowedAttributes",
                 "vacationFlag",
                 "eduPersonPrimaryAffiliation",
                 "eduPersonAffiliation",
                 "studentResidenceHallBldg",
                 "studentResidenceHallRoom",
                 "studentResidenceHallPhone",
                 "studentMajor",
                 "studentMinor",
                 "studentSpecialization",
                 "studentYearInSchool",
                 "studentAdvisor",
                 "studentPostOffice",
                 "title",
                 "officeBldg",
                 "officePhone",
                 "mobile",
                 "postalAddress",
                 "l",
                 "st",
                 "lutherC",
                 "postalcode",
                 "telephoneNumber",
                 "spouseName",
                 "childName",
                 "employeenumber",
                 "prno",
                 "studentStatus",
                 "departmentName",
                 "gender",
                 "termEnrolled",
                 "ocPostalAddress",
                 "ocL",
                 "ocSt",
                 "ocC",
                 "ocPostalCode",
                 "ocPhone",
                 "lastUpdate",
                 "privacyFlag",
                 "creationDate",
                 "deleteAfterDate",
                 "birthDate",
                 "lastTermAttended",
                 "programStartDate",
                 "programEndDate",
                 "studentStatusDate");
  
  if ($_SESSION['logged_in'] && 
      (is_admin($ds, $_SESSION['user']) || ($name == $_SESSION['user']))) {
    $filter = "(uid=$name)";
  } else {
    $filter = "(& (uid=$name)(!(privacyFlag=*)))";
  }

  $result = @ldap_search($ds, $peopledn, $filter, $attr);
  $user = @ldap_get_entries($ds, $result);


  if ($user['count'] != 1) {
    echo "<tr><td colspan=2>
          <b>$name</b> wasn't found in the database
          </td></tr></table>";
    require_once("./foot.html");
    exit(1);
  } else {
    echo "<tr valign=top><td>
          <table border=0 cellpadding=3 cellspacing=0>
          <tr valign=top>
          <td align=right><b>Name:</b></td>
          <td>"
          .$user[0]['cn'][0].
          "</td></tr>";

    if (is_admin($ds, $_SESSION['user'])) {
      echo "<tr><td align=right valign=top nowrap><b>All Names:</b></td><td>";
      for ($i = 0; $i < $user[0]['cn']['count']; $i++) {
        echo $user[0]['cn'][$i]."<br>";
      }
      echo "</td></tr>";
    }

    echo "<tr valign=top>
          <td align=right><b>Username:</b></td>
          <td>"
          .$user[0]['uid'][0].
          "</td></tr><tr valign=top>
          <td align=right><b>E-mail:</b></td>
          <td><a href='mailto:"
          .$user[0]['mail'][0].
          "'>"
          .$user[0]['mail'][0].
          "</a></td></tr>";

    if (isset($_SESSION['primary_email_changed'])) {
      //echo "<tr valign=top>
      //      <td align=right></td>
      //      <td><small><font color=red>
      //      primary email has changed and will<br>automatically be made visible</small></font>
      //      </td></tr>";
      // primary email not functioning 22 Nov 2005 - BJ
      echo "<tr valign=top>
            <td align=right></td>
            <td><small><font color=red>
            primary email has been scheduled for update<br>and will be available within 2 hours.</small></font>
            </td></tr>";
      unset($_SESSION['primary_email_changed']);
      unset($_SESSION['primary_email_hidden']);
    }
    else if (isset($_SESSION['primary_email_hidden'])) {
      echo "<tr valign=top>
            <td align=right></td>
            <td><small><font color=red>
            primary email will automatically<br>be made visible</small></font>
            </td></tr>";
      unset($_SESSION['primary_email_changed']);
      unset($_SESSION['primary_email_hidden']);
    }
 
    if ($user[0]['milterforwardaddress']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right nowrap><b>Forwarding E-mail:</b></td>
            <td>";

            for ($i = 0; $i < $user[0]['milterforwardaddress']['count']; $i++) {
              echo $user[0]['milterforwardaddress'][$i]."<br>";
            }

      echo "</td>
            </tr>";
    }

    // email aliases
    $selfallowed_attrs = explode(":", $user[0]['selfallowedattributes'][0]);
    $aliases = array();
    for ($j = 0; $j < count($user[0]['maillocaladdress']) - 1; $j++) {
      $aliasVis = "mailLocalAddressVis".$j;
        if (in_array($aliasVis, $selfallowed_attrs)
          && $user[0]['mail'][0] != $user[0]['maillocaladdress'][$j]) {
        //echo $user[0]['maillocaladdress'][$j]."<br />";
          array_push($aliases, $user[0]['maillocaladdress'][$j]);
      }
    }

    if (count($aliases) > 0) {
      echo "<tr valign=top>
            <td align=right nowrap><b>Other E-mail:</b></td>
            <td>";

      for ($j = 0; $j < count($aliases); $j++) {
        echo $aliases[$j]."<br />";
      }

      echo "</td>
            </tr>";
    }


    if ($user[0]['edupersonprimaryaffiliation']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right><b>Affiliation:</b></td>
            <td>"
            .$user[0]['edupersonprimaryaffiliation'][0].
            "</td></tr>";
    }

    if ($user[0]['edupersonaffiliation']['count'] > 0) {
      echo "<tr><td align=right valign=top nowrap><b>All Affiliations:</b></td>
            <td>";

      for ($i = 0; $i < $user[0]['edupersonaffiliation']['count']; $i++) {
        echo $user[0]['edupersonaffiliation'][$i]."<br>";
      }

      echo "</td></tr>";
    }

    if ($user[0]['studentresidencehallbldg']['count'] > 0) {
      echo "<tr valign=top><td align=right><b>Housing:</b></td><td>"
           .$user[0]['studentresidencehallbldg'][0].
           ", Room "
           .$user[0]['studentresidencehallroom'][0].
           "</td></tr>";
    }

    if ($user[0]['studentresidencehallphone']['count'] > 0) {
      echo "<tr valign=top><td align=right nowrap><b>Campus Phone:</b></td><td>"
           .$user[0]['studentresidencehallphone'][0].
           "</td></tr>";
    }

    if ($user[0]['officephone']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right nowrap><b>Office Phone:</b></td>
            <td>"
            .$user[0]['officephone'][0].
            "</td></tr>";
    }


        if ($user[0]['mobile']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right nowrap><b>Mobile:</b></td>
            <td>"
            .$user[0]['mobile'][0].
            "</td></tr>";
    }


    if ($user[0]['studentmajor']['count'] > 0) {
      echo "<tr valign=top><td align=right><b>Major(s):</b></td><td>";

      for ($i = 0; $i < $user[0]['studentmajor']['count']; $i++) {
        echo $user[0]['studentmajor'][$i]."<br>";
      }

      echo "</td></tr>";
    }

    if ($user[0]['studentminor']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right><b>Minor(s):</b></td>
            <td>";

      for ($i = 0; $i < $user[0]['studentminor']['count']; $i++) {
        echo $user[0]['studentminor'][$i]."<br>";
      }

      echo "</td></tr>";
    }
    
    if ($user[0]['studentspecialization']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right><b>Specialization:</b></td>
            <td>"
            .$user[0]['studentspecialization'][0].
            "</td></tr>";
    }
    
    if ($user[0]['studentyearinschool']['count'] > 0) {
      echo "<tr valign=top><td align=right nowrap><b>Year In School:</b></td><td>"
           .$user[0]['studentyearinschool'][0].
           "</td></tr>";
    }

    if ($user[0]['studentadvisor']['count'] > 0) {
      $attr = array("cn");
      $result = @ldap_search($ds, $peopledn, "uid=".$user[0]['studentadvisor'][0], $attr);
      @ldap_sort($ds, $result, "cn");
      $advisor = @ldap_get_entries($ds, $result);

      echo "<tr valign=top>
            <td align=right><b>Advisor:</b></td>
            <td><a href='"
            .$_SERVER['PHP_SELF'].
            "?name="
            .$user[0]['studentadvisor'][0].
            "'>"
            .$advisor[0]['cn'][0].
            "</a></td></tr>";
    }

    // Look up and advisees the user (advisor) might have
    if (is_admin($ds, $_SESSION['user']) ||
        ($name == $_SESSION['user'])) {
      $attr = array("uid", "cn");

      $filter = "(studentAdvisor=".$user[0]['uid'][0].")";
      $result = @ldap_search($ds, $peopledn, $filter, $attr);
      @ldap_sort($ds, $result, "cn");
      $advisees = @ldap_get_entries($ds, $result);

      if ($advisees['count'] > 0) {
        echo "<tr valign=top>
              <td align=right><b>Advisees:</b></td>
              <td>";

        for ($i = 0; $i < $advisees['count']; $i++) {
          echo "<a href='"
               .$_SERVER['PHP_SELF'].
               "?name="
               .$advisees[$i]['uid'][0].
               "'>"
               .$advisees[$i]['cn'][0].
               "</a><br>";
        }

        echo "</td></tr>";
      }
    }
      
    if ($user[0]['studentpostoffice']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right><b>SPO:</b></td>
            <td>"
            .$user[0]['studentpostoffice'][0].
            "</td></tr>";
    }
  
    if ($user[0]['title']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right><b>Title:</b></td>
            <td>"
            .$user[0]['title'][0].
            "</td></tr>";
    }

    if ($user[0]['officebldg']['count'] > 0) {
      echo "<tr valign=top><td align=right nowrap><b>Office Location:</b></td>
            <td>"
            .$user[0]['officebldg'][0].
            "</td></tr>";
    }


    if ($user[0]['postaladdress']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right nowrap><b>Home Address:</b></td>
            <td>"
            .$user[0]['postaladdress'][0].
            "<br>"
            .$user[0]['l'][0].
            ", "
            .$user[0]['st'][0].
            "<br>"
            .$user[0]['postalcode'][0].
            "<br>"
//          .$user[0]['c'][0].
//            "</td></tr>";
            .$user[0]['lutherc'][0].
            "</td></tr>";
    }

    if ($user[0]['telephonenumber']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right nowrap><b>Home Phone:</b></td>
            <td>"
            .$user[0]['telephonenumber'][0].
            "</td></tr>";
    }

    if (($user[0]['spousename']['count'] > 0) &&
        (strlen(trim($user[0]['spousename'][0])) > 0)) {
      echo "<tr valign=top>
            <td align=right nowrap><b>Spouse:</b></td>
            <td>"
            .$user[0]['spousename'][0].
            "</td></tr>";
    }
    
    if ($user[0]['childname']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right nowrap><b>Children:</b></td>
            <td>";

      for ($i = 0; $i < $user[0]['childname']['count']; $i++) {
        echo $user[0]['childname'][$i]."<br>";
      }

      echo "</td></tr>";
    }

    if ($user[0]['employeenumber']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right nowrap><b>Luther ID:</b></td>
            <td>"
            .$user[0]['employeenumber'][0].
            "</td></tr>";
    }

    if ($user[0]['prno']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right><b>PRNO:</b></td>
            <td>"
            .$user[0]['prno'][0].
            "</td></tr>";
    }
    
    if ($user[0]['studentstatus']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right nowrap><b>Student Status:</b></td>
            <td>"
            .$user[0]['studentstatus'][0].
            "</td></tr>";
    }
    
    if ($user[0]['departmentname']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right><b>Department:</b></td>
            <td>"
            .$user[0]['departmentname'][0].
            "</td></tr>";
    }

    if ($user[0]['gender']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right><b>Gender:</b></td>
            <td>"
            .$user[0]['gender'][0].
            "</td></tr>";
    }
    
    if ($user[0]['termenrolled']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right nowrap><b>Terms Enrolled:</b></td>
            <td>";

      for ($i = 0; $i < $user[0]['termenrolled']['count']; $i++) {
        echo $user[0]['termenrolled'][$i]."<br>";
      }

      echo "</td></tr>";
    }
    
    if ($user[0]['ocpostaladdress']['count'] > 0) {
        echo "<tr valign=top>
              <td align=right nowrap><b>Off Campus Address:</b></td>
              <td>"
              .$user[0]['ocpostaladdress'][0].
              "<br>"
              .$user[0]['ocl'][0].
              ", "
              .$user[0]['ocst'][0].
              "<br>"
              .$user[0]['ocpostalcode'][0].
              "<br>"
              .$user[0]['occ'][0].
              "</td></tr>";
    }

    if ($user[0]['ocphone']['count'] > 0) {
        echo "<tr valign=top>
              <td align=right nowrap><b>Off Campus Phone:</b></td>
              <td>"
              .$user[0]['ocphone'][0].
              "</td></tr>";
    }
    
    if ($user[0]['privacyflag']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right nowrap><b>Privacy Flag:</b></td>
            <td>"
            .$user[0]['privacyflag'][0].
            "</td></tr>";
    }

    if ($user[0]['creationdate']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right nowrap><b>Creation Date:</b></td>
            <td>"
            .$user[0]['creationdate'][0].
            "</td></tr>";
    }

    if ($user[0]['deleteafterdate']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right nowrap><b>Delete After Date:</b></td>
            <td>"
            .$user[0]['deleteafterdate'][0].
            "</td></tr>";
    }

    if ($user[0]['birthdate']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right nowrap><b>Birth Date:</b></td>
            <td>"
            .$user[0]['birthdate'][0].
            "</td></tr>";
    }

    if ($user[0]['lasttermattended']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right nowrap><b>Last Term Attended:</b></td>
            <td>"
            .$user[0]['lasttermattended'][0].
            "</td></tr>";
    }

    if ($user[0]['programstartdate']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right nowrap><b>Program Start Date:</b></td>
            <td>"
            .$user[0]['programstartdate'][0].
            "</td></tr>";
    }

    if ($user[0]['programenddate']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right nowrap><b>Program End Date:</b></td>
            <td>"
            .$user[0]['programenddate'][0].
            "</td></tr>";
    }

    if ($user[0]['studentstatusdate']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right nowrap><b>Student Status Date:</b></td>
            <td>";

      for ($i = 0; $i < $user[0]['studentstatusdate']['count']; $i++) {
        echo $user[0]['studentstatusdate'][$i]."<br>";
      }

      echo "</td></tr>";
    }

    if ($user[0]['lastupdate']['count'] > 0) {
      echo "<tr valign=top>
            <td align=right nowrap><b>Last Updated:</b></td>
            <td>"
            .$user[0]['lastupdate'][0].
            "</td></tr>";
    }

    if (is_admin($ds, $_SESSION['user'])) {
      $attr = array("cn", "description");
      $result = @ldap_search($ds, $groupdn, "memberUid=$name", $attr);
      @ldap_sort($ds, $result, "cn");
      $groups = @ldap_get_entries($ds, $result);

      if ($groups['count'] > 0) {
        print "<tr valign=top>
                 <td valign=top align=right><b>Member Of:</b></td>
                 <td>";

        for ($i = 0; $i < $groups['count']; $i++) {
          echo "<a href='group.php?name="
               .urlencode($groups[$i]['cn'][0]).
               "'>"
               .$groups[$i]['cn'][0].
               "</a> ("
               .$groups[$i]['description'][0].
               ")<br>";
        }

        print "</td></tr>";
      }
    }

    echo "</table>
          </td><td align=right>";

    if ($_SESSION['logged_in']) {
      if (is_admin($ds, $_SESSION['user']) ||
          ($user[0]['uid'][0] == $_SESSION['user']) ||
          ((user_has_affiliation($ds, $user[0]['uid'][0], "Student") ||
            user_has_affiliation($ds, $user[0]['uid'][0], "Student - Not Enrolled this Term") ||
            user_has_affiliation($ds, $user[0]['uid'][0], "Student - Planning to Enroll")) &&
           (user_has_affiliation($ds, $_SESSION['user'], "Faculty") ||
            user_has_affiliation($ds, $_SESSION['user'], "Staff") ||
            user_has_affiliation($ds, $_SESSION['user'], "Emeritus"))) ||
          ((user_has_affiliation($ds, $user[0]['uid'][0], "Faculty") ||
            user_has_affiliation($ds, $user[0]['uid'][0], "Staff") ||
            user_has_affiliation($ds, $user[0]['uid'][0], "Emeritus")) &&
           (user_has_affiliation($ds, $_SESSION['user'], "Faculty") ||
            user_has_affiliation($ds, $_SESSION['user'], "Staff") ||
            user_has_affiliation($ds, $_SESSION['user'], "Emeritus") ||
            user_has_affiliation($ds, $_SESSION['user'], "Student") ||
            user_has_affiliation($ds, $_SESSION['user'], "Student - Not Enrolled this Term") ||
            user_has_affiliation($ds, $_SESSION['user'], "Student - Planning to Enroll")))) {

        $fp = @fopen($photo_dir.$name.".jpg", "r");
      }

      if ($fp) {
        fclose($fp);
        $_SESSION['load_img'] = 1;
        echo "<img src='img.php?user=$name' width=141>";
      } else {
        echo "&nbsp;";
      }
    } else {
      echo "&nbsp;";
    }

    echo "</td></tr>";
  }

?>
  </table>
  <center>
<?php
  if (isset($_SESSION['logged_in']) &&
     (($_SESSION['user'] == $name) || is_admin($ds, $_SESSION['user']))) {
    echo "<br>
          <table bgcolor=#eeeeee border=0 align=center cellpadding=5 cellspacing=0>
          <tr><td>
          <a href='"
         .$user_form_action.
         "?mode=edit&name="
         .urlencode($name).
         "'>Edit Entry</a>
         </td></tr></table>";
  }
?>
  </center>
<?php
  
  @ldap_unbind($ds);
}
require_once("./foot.html");
?>
