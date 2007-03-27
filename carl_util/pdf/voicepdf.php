<?php

include ( 'paths.php' );
include_once( CARL_UTIL_INC . 'pdf/pdfdoc.php' );

class VoicePDF extends PDFDoc
{
    var $issue_name;
    var $issue_volume;
    var $tissue_number;

    function VoicePDF( $id, $type )
    {   
        include("/usr/local/etc/php3/dbstuff.php3");
	
        $result = "";

        // connect to server
	$db= @mysql_connect($dbhost, $dbuser, $dbpasswd) or die("Not able to connect to the server at this time. Please try again later.");
	
	// select database
	@mysql_select_db('voice', $db) or die("Not able to connect to the database at this time. Please try again later.");
        
        if( $type == 'features' )
        {
            if( !($result = mysql_query("SELECT issue.name, issue.volume, issue.number, features.title, features.teaser, features.story, features.author FROM issue LEFT JOIN features ON issue.issue_id = features.issue_id WHERE features_id='$id'",$db) ) )
                echo 'could not query database';
        }
        elseif( $type == 'departments' )
        {    
            if( !($result = mysql_query("SELECT issue.name, issue.volume, issue.number, departments.title, departments.metaDesc, departments.story FROM issue LEFT JOIN departments ON issue.issue_id = departments.issue_id WHERE article='$id'",$db) ) )
                echo 'could not query database';
        }
       
        if($result != ""){ // sometimes the type gets mangled and the mysql_query never happens. If so, give something sensible.
            while($row = mysql_fetch_row($result)) 
            {
                $this->issue_name = $row[0];
                $this->issue_volume = $row[1];
                $this->issue_number = $row[2];
                $this->title = $row[3];
                $this->teaser = $row[4];
                $this->story = $row[5];
                if( $type == 'features' )
                    $this->author = $row[6];
                }
            }
        else {
            $this->issue_name = 'Issue Unspecified';
            $this->issue_volume = 0 ; 
            $this->issue_number = 0 ; 
            $this->title = "Title Unspecified";
            $this->teaser = "Article? No.";
            $this->story = "The article you have reached is not in service. Please check the URL and try again. If you feel you have recieved this message in error, please contact mbockol@carleton.edu .";
            }




    }
    
    function show_title()
    {
        $this->my_set_font( 'em_p' );
        $this->pdf_print_txt( 'Carleton College Voice' );
        $this->pdf_print_txt( 'Issue: '.$this->issue_name );
        $this->pdf_print_txt( 'Volume: '.$this->issue_volume.' Number: '.$this->issue_number );
        $this->new_line();
        $this->my_set_font( 'h1' );
        $this->toggle( 'h1' );
        $this->pdf_print_txt( $this->title );
        $this->toggle( 'h1' );
        $this->my_set_font( 'em_p' );
        if( !empty( $this->author ) )
            $this->pdf_print_txt( 'by '.$this->author );
        $this->new_line();
        $this->new_line();
    }
}
