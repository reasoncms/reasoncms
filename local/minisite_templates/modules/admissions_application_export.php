<?php
//	reason_include_once( 'minisite_templates/modules/default.php' );
//	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'testModule';
//	class testModule extends DefaultMinisiteModule
//	{
//		function init( $args = array() )
//		{
//			
//		}
//		function has_content()
//		{
//			return true;
//		}
//		function run()
//		{
//			echo '<div class="test">Hello World</div>'."\n";
//		}
//	}

connectDB('admissions_applications_connection');
$qstring = "SELECT student_type, enrollment_term, citizenship_status, first_name FROM `applicants`";
$results = db_query($qstring);
while ($row = mysql_fetch_array($results, MYSQL_ASSOC))
{
    echo $row['first_name'];   
}

?>
