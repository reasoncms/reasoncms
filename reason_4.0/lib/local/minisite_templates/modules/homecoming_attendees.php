<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'HomecomingAttendeesModule';
	
	class HomecomingAttendeesModule extends DefaultMinisiteModule
	{

        function get_attendees() 
        {
        	connectDB('homecoming_connection');
    		
    		$qstring = 'SELECT  `first_name` ,  `preferred_first_name` ,  `last_name` ,  `class_year`,  `graduation_name` FROM  `registrants` WHERE `class_year` IS NOT NULL';
           

   			$results = db_query($qstring);
   			
   		

    		connectDB(REASON_DB);
    		return $results;
    	}
		function run()
		{
			$results = $this->get_attendees();
			

            $str = '';
            $str .= '<table id="attendees" class="tablesorter" border="0" cellpadding="0" cellspacing="0">';
            $str .= '<thead>';
                $str .= '<tr>';
                        $str .= '<th>First Name</th>';
                        $str .= '<th>Last Name</th>';
                        $str .= '<th>Graduation Name</th>';
                        $str .= '<th>Class</th>';
                        
                   
         
                $str .= '</tr>';
            $str .= '</thead>';
       		$str .= '<tbody>';
       		echo $str;
         
            while ($row = mysql_fetch_array($results, MYSQL_ASSOC)){
            	echo '<tr>';
            	if ($row['preferred_first_name'] && ($row['preferred_first_name'] != 'NULL'))
            	{
            		echo '<td>' .$row['preferred_first_name']. '</td>';
            	}
            	else{
            		echo '<td>' .$row['first_name']. '</td>';
            	}


            	echo '<td>' .$row['last_name']. '</td>';
            	$gn = strtolower($row['graduation_name']);
            	if ($gn && ($gn != 'same'))
            	{
            		echo '<td>' .$row['graduation_name']. '</td>';
            	}else{
            		echo '<td>'. $row['first_name'] . ' ' . $row['last_name'] . '</td>';
            	}
            	
            	echo '<td>' .$row['class_year']. '</td>';
            	echo '</tr>';


            }
            $str = '</tbody>';
            $str = '</table>';
            echo $str;



		}
		
	}
?>