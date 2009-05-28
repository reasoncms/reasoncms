
<?

phpinfo();

die;


      //$conn = sqlanywhere_connect( "server=odyssey.luther.edu;db=odyssey;uid=pcsuser;pwd=pwd" );
	$conn = sasql_connect( "server=odyssey.luther.edu;db=odyssey;uid=pcsuser;pwd=pwd" );



      if( ! $conn ) {
            echo "Connection failed\n"; } else {
            echo "Connected successfully\n";
            sqlanywhere_disconnect( $conn ); }

?>


