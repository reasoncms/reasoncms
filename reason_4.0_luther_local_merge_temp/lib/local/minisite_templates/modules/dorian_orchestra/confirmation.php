<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Lucas Welper
//    2010-11-03
//
//    Work on the confirmation page of the dorian orchestra nomination form
//
////////////////////////////////////////////////////////////////////////////////


//require_once( CARL_UTIL_INC . 'db/db.php');
require_once( '/usr/local/webapps/reason/reason_package/carl_util/db/db.php' );
include_once(TYR_INC . 'tyr.php');
include_once('paths.php');


class ConfirmationForm extends FormStep
{
	var $_log_errors = true;
	var $error;
        var $display_name = 'Dorian Orchestra Festival Nomination Director Information';
        //var $confirmEmailAddress = 'buzzja01@luther.edu';
        var $confirmEmailAddress = 'welplu01@luther.edu';
        //var $ccEmailAddress = 'welplu01@luther.edu';
        var $confirmText;

        var $directorId;
        var $directorFirstName;
        var $directorLastName;
        var $directorEmail;
        var $schoolName;
        var $schoolPhone;
        var $schoolAddress;
        var $schoolCity;
        var $schoolState;
        var $schoolZip;

        var $studentCount;


	function on_every_time()
	{
                //number of students
                $this->studentCount = $_SESSION['student_count'];

                //director info
                $this->directorFirstName = $_SESSION['dorian_orchestra_fc_data']['director_first_name'];
                $this->directorLastName = $_SESSION['dorian_orchestra_fc_data']['director_last_name'];
                $this->directorEmail = $_SESSION['dorian_orchestra_fc_data']['director_email'];
                $this->schoolName = $_SESSION['dorian_orchestra_fc_data']['school_name'];
                $this->schoolPhone = $_SESSION['dorian_orchestra_fc_data']['school_phone'];
                $this->schoolAddress = $_SESSION['dorian_orchestra_fc_data']['school_street_address'];
                $this->schoolCity = $_SESSION['dorian_orchestra_fc_data']['school_city'];
                $this->schoolState = $_SESSION['dorian_orchestra_fc_data']['school_state'];
                $this->schoolZip = $_SESSION['dorian_orchestra_fc_data']['school_zip'];


                if ($this->studentCount >= 1){
                        $this->save_director_info();
                        $confirmText = $this->build_summary();
                        $this->email_confirmation($confirmText);
                        $this->display_summary($confirmText);
                        session_destroy();
                }else{
                    echo "Session expired - Please start over.";
                }

		$this->show_form = false;
	}

        function save_director_info(){

                connectDB('dorian_orchestra_connection');

                $qstring = "INSERT INTO `directors` SET ";
                $qstring .= "first_name = '".addslashes($this->directorFirstName)."', ";
                $qstring .= "last_name = '".addslashes($this->directorLastName)."', ";
                $qstring .= "email = '".addslashes($this->directorEmail)."', ";
                $qstring .= "school_name = '".addslashes($this->schoolName)."', ";
                $qstring .= "school_phone = '".addslashes($this->schoolPhone)."', ";
                $qstring .= "school_address = '".addslashes($this->schoolAddress)."', ";
                $qstring .= "school_city = '".addslashes($this->schoolCity)."', ";
                $qstring .= "school_state = '".addslashes($this->schoolState)."', ";
                $qstring .= "school_zip = '".addslashes($this->schoolZip)."'; ";

                $qresult = db_query($qstring);
                $this->directorId = mysql_insert_id();

                for($count = $this->studentCount; $count > 0; $count--){

                        $currentStudent = 'student'.$count;
                        //$part1 = $_SESSION[$currentStudent]['desired_participation'][0];
                        //$part2 = $_SESSION[$currentStudent]['desired_participation'][1];

                        $part1 = (in_array('ml', $_SESSION[$currentStudent]['desired_participation']) ? 'ml' : '');
                        $part2 = (in_array('cc', $_SESSION[$currentStudent]['desired_participation']) ? 'cc' : '');
                        $accompanist = in_array('ac', $_SESSION[$currentStudent]['desired_participation']) ? 'Y' : 'N';

                        $qstring = "INSERT INTO `students` SET ";
                        $qstring .= "director_id=".$this->directorId.", ";
                        $qstring .= "first_name='".addslashes($_SESSION[$currentStudent]['student_first_name'])."', ";
                        $qstring .= "last_name='".addslashes($_SESSION[$currentStudent]['student_last_name'])."', ";
                        $qstring .= "gender='".addslashes($_SESSION[$currentStudent]['student_gender']{0})."', ";
                        $qstring .= "email='".addslashes($_SESSION[$currentStudent]['student_email'])."', ";
                        $qstring .= "school_name='".addslashes($_SESSION[$currentStudent]['student_school_name'])."', ";
                        $qstring .= "phone='".addslashes($_SESSION[$currentStudent]['student_phone'])."', ";
                        $qstring .= "address='".addslashes($_SESSION[$currentStudent]['student_street_address'])."', ";
                        $qstring .= "city='".addslashes($_SESSION[$currentStudent]['student_city'])."', ";
                        $qstring .= "state='".addslashes($_SESSION[$currentStudent]['student_state'])."', ";
                        $qstring .= "zip='".addslashes($_SESSION[$currentStudent]['student_zip'])."', ";
                        $qstring .= "voice_part='".addslashes($_SESSION[$currentStudent]['voice_part'])."', ";
                        $qstring .= "rank='".addslashes($_SESSION[$currentStudent]['rank'])."', ";
                        $qstring .= "year_in_school='".addslashes($_SESSION[$currentStudent]['year_in_school'])."', ";
                        $qstring .= "years_singing_exp='".addslashes($_SESSION[$currentStudent]['years_of_singing_experience'])."', ";
                        $qstring .= "desired_part='".addslashes(($part1 ? $part1 : '') . ($part1 && $part2 ? ',' : '') . ($part2 ? $part2 : ''))."', ";
                        $qstring .= "accompanist='".addslashes($accompanist)."', ";
                        $qstring .= "overnight_housing='".addslashes($_SESSION[$currentStudent]['housing_needed'])."', ";
                        $qstring .= "comment='".addslashes($_SESSION[$currentStudent]['director_comments'])."' ";
                        $qstring .= ";";

                        $qresult = db_query($qstring);
                }

                connectDB(REASON_DB);
        }
        function email_confirmation($text){

		$mail = new Email($this->confirmEmailAddress, 'noreply@luther.edu','noreply@luther.edu', 'New Dorian Orchestra Registration '.date('mdY H:i:s'),strip_tags($text), $text);
		$mail->send();
                $mail = new Email($this->ccEmailAddress, 'noreply@luther.edu','noreply@luther.edu', 'New Dorian Orchestra Registration '.date('mdY H:i:s'),strip_tags($text), $text);
		$mail->send();
                
        }
        function display_summary($text){
            echo $text;
        }
        function build_summary(){

                $text = "Thank you!  The information below has been received.  <br /><b>We recommend that you print a copy of this for your records.</b><br /><br />"."\n";
                $text .= "<style type='text/css'>"."\n";
                $text .= "  table.mytestclass td { padding:0px 10px 0px 0px; text-align:left; vertical-align:text-top; }"."\n";
                $text .= "  td.nowrap { white-space: nowrap; }"."\n";
                $text .= "</style>"."\n";
                $text .= "<fieldset>"."\n";
                $text .= "  <legend><h4>Director Info</h4></legend>"."\n";
                $text .= "    <table style='width:'>"."\n";
                $text .= "      <tr>"."\n";
                $text .= "        <td>Name: </td><td>" . $this->directorFirstName . " " . $this->directorLastName . "</td>"."\n";
                $text .= "      </tr>"."\n";
                $text .= "      <tr>"."\n";
                $text .= "        <td>Email: </td><td>" . $this->directorEmail . "</td>"."\n";
                $text .= "      </tr>"."\n";
                $text .= "      <tr>"."\n";
                $text .= "        <td>Phone: </td><td>" . $this->schoolPhone . "</td>"."\n";
                $text .= "      </tr>"."\n";
                $text .= "      <tr>"."\n";
                $text .= "        <td>School: </td><td>" . $this->schoolName . "</td>"."\n";
                $text .= "      </tr>"."\n";
                $text .= "      <tr>"."\n";
                $text .= "        <td>Address:&nbsp;<br />&nbsp;</td><td>" . $this->schoolAddress . "<br />" . $this->schoolCity . ", " . $this->schoolState . " " . $this->schoolZip . "</td>"."\n";
                $text .= "      </tr>"."\n";
                $text .= "    </table>"."\n";
                $text .= "</fieldset>"."\n";
                $text .= "<br />"."\n";
                $text .= "<fieldset>"."\n";
                $text .= "  <legend><h4>Submitted Students</h4></legend>"."\n";
                $text .= "    <table class='mytestclass'>"."\n";

                for($count = $this->studentCount; $count > 0; $count--)
                {
                    $currentStudent = 'student'.$count;
                    //$part1 = $_SESSION[$currentStudent]['desired_participation'][0];
                    //$part2 = $_SESSION[$currentStudent]['desired_participation'][1];
                    $part1 = (in_array('ml', $_SESSION[$currentStudent]['desired_participation']) ? 'ml' : '');
                    $part2 = (in_array('cc', $_SESSION[$currentStudent]['desired_participation']) ? 'cc' : '');
                    $accompanist = in_array('ac', $_SESSION[$currentStudent]['desired_participation']) ? 'Y' : 'N';
                    if($part1=='cc'){$part1 = 'Chamber Choir';}
                    if($part2=='cc'){$part2 = 'Chamber Choir';}
                    if($part1=='ml'){$part1 = 'Mini-Lesson'.($accompanist == 'Y' ? ' (/w accomp.)' : '');}
                    if($part2=='ml'){$part2 = 'Mini-Lesson'.($accompanist == 'Y' ? ' (/w accomp.)' : '');}

                    $text .= "<tr>"."\n";
                    $text .= "<td colspan='4'><b>Name:&nbsp;".$_SESSION[$currentStudent]['student_first_name'] . " " . $_SESSION[$currentStudent]['student_last_name']."</b></td>"."\n";
                    $text .= "</tr>"."\n";
                    $text .= "<tr>"."\n";
                    $text .= "        <td class='nowrap'><b>Email:</b><br />"."\n";
                    $text .= "            <b>Phone:</b><br />"."\n";
                    $text .= "            <b>Address:</b><br />&nbsp;<br />"."\n";
                    $text .= "            <b>Overnight Housing:&nbsp;</b><br />&nbsp;"."\n";
                    $text .= "        </td>"."\n";
                    $text .= "        <td>" . "\n";
                    $text .= "            " . $_SESSION[$currentStudent]['student_email'] . "<br />"."\n";
                    $text .= "            " . $_SESSION[$currentStudent]['student_phone'] . "<br />"."\n";
                    $text .= "            " . $_SESSION[$currentStudent]['student_street_address'] . "<br />"."\n";
                    $text .= "            " . $_SESSION[$currentStudent]['student_city'] . ", " . $_SESSION[$currentStudent]['student_state'] . " " . $_SESSION[$currentStudent]['student_zip']."<br />"."\n";
                    $text .= "            " . $_SESSION[$currentStudent]['housing_needed'] . "<br />"."\n";
                    $text .= "        </td>"."\n";
                    $text .= "        <td style='white-space:nowrap;'>"."\n";
                    $text .= "            <b>Gender:</b><br />"."\n";
                    $text .= "            <b>Voice Part:</b><br />"."\n";
                    $text .= "            <b>Rank:</b><br />"."\n";
                    $text .= "            <b>Year In School:</b><br />"."\n";
                    $text .= "            <b>Yrs Singing Exp:</b><br />"."\n";
                    $text .= "            <b>Desired Part.:</b><br />"."\n";
                    $text .= "        </td>"."\n";
                    $text .= "        <td>"."\n";
                    $text .= "        " . $_SESSION[$currentStudent]['student_gender']."<br />"."\n";
                    $text .= "        " . $_SESSION[$currentStudent]['voice_part']."<br />"."\n";
                    $text .= "        " . $_SESSION[$currentStudent]['rank'] . "<br />"."\n";
                    $text .= "        " . $_SESSION[$currentStudent]['year_in_school']. "<br />"."\n";
                    $text .= "        " . $_SESSION[$currentStudent]['years_of_singing_experience']. "<br />"."\n";
                    $text .= "        " . ($part1 ? $part1 : '') . ($part1 && $part2 ? ',' : '') . ($part2 ? $part2 : '') . "<br />"."\n";
                    $text .= "        </td>"."\n";
                    $text .= "</tr>"."\n";
                    $text .= "<tr>"."\n";
                    $text .= "        <td colspan='4'><b>Comments:</b>&nbsp;".$_SESSION[$currentStudent]['director_comments'] . "</td>";
                    $text .= "</tr>"."\n";
                    $text .= "<tr><td colspan='4'>&nbsp;</td></tr>"."\n";
                }

                $text .= "    </table>"."\n";
                $text .= "</fieldset>"."\n";

                return $text;
        }

        function pre_show_form()
	{
		echo '<div id="dorianBandForm" class="directorForm">'."\n";
	}
	function post_show_form()
	{
		echo '</div>'."\n";
	}
}
?>