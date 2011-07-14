<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	reason_include_once( 'classes/error_handler.php');
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'admissionsApplicationExportModule';
	
	class admissionsApplicationExportModule extends DefaultMinisiteModule
	{
		
		function init( $args = array() )
		{
		}
		
		function has_content()
		{
			return true;
		}
		
		function run()
		{
                    connectDB('admissions_applications_connection');
                    $qstring = "SELECT student_type, enrollment_term, citizenship_status, first_name, last_name FROM `applicants` WHERE first_name IS NOT NULL";
                    $results = db_query($qstring);
                    connectDB(REASON_DB);
                    echo 'Please highlight the applicant(s) you wish to import to Datatel.  Copy and paste the data into a word document.  Then one by one copy each piece of data into the correct field in Datatel.';
                        
                    while ($row = mysql_fetch_array($results, MYSQL_ASSOC))
                    {
                        switch(rand(1,4) % 4) {
                            case 0:
                                $color = "red";
                                $color2 = "purple";
                                break;
                            case 1:
                                $color = "blue";
                                $color2 = "green";
                                break;
                            case 2:
                                $color = "brown";
                                $color2 = "pink";
                                break;
                            case 3:
                                $color = "purple";
                                $color2 = "yellow";
                                break;
                        }
                        echo '<FONT SIZE="4" FACE="courier" COLOR='.$color.'><MARQUEE WIDTH=100% BEHAVIOR=SCROLL BGColor='.$color2.' LOOP=3 scrollamount='.rand(3,7).'>';
                        echo $row['first_name']." ".$row['last_name']." ".$row['student_type']." ".$row['enrollment_term']." ".$row['citizenship_status'];
                        echo "</br>";
                        echo '</MARQUEE></FONT>';
                    }
                }
        }
?>
