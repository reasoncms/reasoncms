<?php
/**
 * A URL shortcut tool - not currently usable out of the box outside of Carleton
 *
 * @package carl_util
 * @subpackage go
 */

/**
 * Include dependencies
 */
include_once ( 'paths.php' );

/**
 * A URL shortcut tool
 *
 * @todo make this its own project or something...
 * @todo factor Carleton-specific code into a settings file
 */
class ShortURL
{
    var $long_URL = '';
    var $short_URL = '';
    var $created_method = 'auto';
    var $created_by = '';
    var $temp_short_URL;
    var $short_URLs = array();
    var $trusted_domains = array( 'carleton.edu', 'collegebookstore.org', 'carletonian.org' );
    var $db;
    var $basedir = 'http://go.carleton.edu'; 
    var $admin = false;
    var $admins = array( 'mryan', 'jlawrenc', 'dbratland', 'tfeiler', 'janderso','trozwadowski', 'mlauer');
    
    function ShortURL()
    {
        include_once( CARL_UTIL_INC . 'db/db.php' );
	$this->db = @connectDB('go_carleton');
	if (empty($this->db)) die ('Not able to connect to the server at this time. Please try again later.');

        if( !isset( $_SERVER['REMOTE_USER'] ) )
        {
            $this->created_by = 'anon';
        }
        else
        {
            $this->created_by = $_SERVER['REMOTE_USER'];
            $this->admin = in_array( $this->created_by, $this->admins );
        }        
    }

    function batch_import( $filename )
    {
        $URLs = file( $filename );
        
        foreach( $URLs as $long_URL )
        {
            $this->set_long_URL( $long_URL );
            $this->long_to_short();
            $this->long_URL = '';
        }
    }
        
    function set_long_URL( $URL )
    {
        $this->validate_URL( $URL )
            or die(    '<p>could not validate URL!<br />' . "\n" .
                    'Possible reasons for this are:<ul>' . "\n" .
                    '<li>You forgot to include "http://" in your URL</li>' . "\n" .
                    '<li>You attempted to add a link to a site not affiliated with Carleton</li>' . "\n" .
                    '<li>You attempted to add a link to an ftp site</li></ul></p>' );
        
        $this->long_URL = $URL;
    }
    
    function set_temp_short_URL( $URL )
    {
        $this->temp_short_URL = $URL;
    }
   
    function long_URL_exists( )
    {
        $query = 'SELECT short_URL FROM redirects WHERE long_URL="' . mysql_real_escape_string($this->long_URL) . '"';
        
        $results = mysql_query( $query, $this->db );
        
        if( mysql_num_rows( $results ) > 0 )
        {
            while( $row = mysql_fetch_row( $results ) )
            {
                array_push( $this->short_URLs, $row[0] );
            }
            return true;
        }
        else
            return false;
    }

	function get_long_URL_for_shortcut($shortcut)
	{
        $query = 'SELECT long_URL FROM redirects WHERE short_URL="' . mysql_real_escape_string($shortcut) . '"';
        
        $results = mysql_query( $query, $this->db );
        
        if( mysql_num_rows( $results ) > 0 )
        {
            if( $row = mysql_fetch_row( $results ) )
            {
                return $row[0];
            }
        }
        else
            return "";
	}

    function short_URL_exists()
    {
        $query = 'SELECT short_URL FROM redirects WHERE short_URL="' . mysql_real_escape_string($this->temp_short_URL) . '"';
        
        $results = mysql_query( $query, $this->db );
        
        if( mysql_num_rows( $results ) > 0 )
        {
            return true;
        }
        else
            return false;
    }

    function set_created_method( $method )
    {
        if( ($method != 'auto') && ($method != 'admin') )
            die( 'Invalid Create Method!');
        else
            $this->created_method = $method;
    }

    /*
    We have to make sure that the URL we are making short conforms 
    to certain rules. 
    1) It must be well-formed (have a scheme and host)
    2) The URL must be from one of our trusted domains (*.carleton.edu, www.collegebookstore.org, www.carletonian.com)
    3) It must be an http(s) request
    */
    function validate_URL( $URL )
    {
        $URL_components = parse_URL( $URL );
        
        if( !( isset( $URL_components['scheme'] ) ) || !( isset( $URL_components['host'] ) ) )
            return false;
        
        if($this->admin)
        	return true;

        //explode our host name on '.'s and reattach the last two with a dot in between... voila, a domain
        $domain_components = explode( '.', $URL_components['host'] );
        $domain = $domain_components[ count( $domain_components ) - 2 ] . '.' . $domain_components[ count( $domain_components ) - 1 ];
        
        if( in_array( $domain, $this->trusted_domains ) && ( $URL_components['scheme'] == 'http' || $URL_components['scheme'] == 'https' ) )
            return true;
        else
            return false;
    }
    
    function get_short_URLs()
    {
        return $this->short_URLs;
    }
        
    /*
    long_to_short() spits out an 8-character hash 
    First, an 8-character string is generated randomly by selecting a hex digit number
    Next, for readability concerns, zeros are replaced with 'z's (0 => z)
    After that, we trim the hash to the smallest unique size
    Finally, collision_detection is called and its return value is our final hash.
    */

    function long_to_short( )
    {
        if( $this->long_URL == '' )
            die( 'Unable to convert empty string!<br />' );
            
        for( $i = 0; $i < 8; $i++ )
        {
            $this->temp_short_URL .= dechex( rand(0, 15) );
        }
        
        $this->temp_short_URL = str_replace( 0, 'z', $this->temp_short_URL );
        $this->trim_short_URL();
        
        $this->collision_detection();
        
        $this->set_short_URL();
    }

    /*
    Here, starting from the front of the hash, we check increasingly large portions of our hash
    against our list of short_URLs. We take the first, non-colliding substring and set our hash 
    equal to it.
    Example: suppose the following strings were already in the database: a, a1. If our hash value 
    were a198z3fd, the trimmed value would be a19. 
    */
    function trim_short_URL()
    {
        $i = 1;
        while( $i < 9 && $this->is_collision( substr( $this->temp_short_URL , 0, $i )) )
        {
            $i++;
        }        
        
        $this->temp_short_URL = substr( $this->temp_short_URL, 0, $i );
    }
    
    /*
    Adds information to wherever we are storing this information
    Right now, that place is memory.
    */
    function store_short_URL( )
    {
        $query =    'INSERT INTO redirects SET ' . 
                    'long_URL="' . mysql_real_escape_string($this->long_URL) . '", ' .
                    'short_URL="' . mysql_real_escape_string($this->temp_short_URL) . '", ' . 
                    'date_added="' . date( 'Y-m-d', time() ) . '", ' . 
                    'created_method="' . mysql_real_escape_string($this->created_method) . '", ' . 
                    'created_by="' . mysql_real_escape_string($this->created_by) . '"';
        
        //echo $query . '<br />';
        
        $result = mysql_query( $query , $this->db ) 
            or die( "Query failed : " . mysql_error());
        
        return true;
    }
    
    /*
    Check our storage area for a given URL
    */
    function is_collision( $URL )
    {
        $query = 'SELECT short_URL FROM redirects WHERE short_URL="' . mysql_real_escape_string($URL) . '"';
        
        $result = mysql_query( $query, $this->db )
                    or die( "Query failed : " . mysql_error());
        
        if( mysql_num_rows( $result ) > 0 )
            return true;
        else
            return false;
    }
    
    /*
    Attempts to store the $this->temp_short_URL, and on success sets short_URL to be that value
    */
    function set_short_URL()
    {
        $tmp = trim( $this->temp_short_URL );
        if( empty( $tmp ) )
        {
            return false;
        }
        else
        {
            if( $this->store_short_URL( ) )
            {
                array_push( $this->short_URLs, $this->temp_short_URL);
                return true;
            }
            else
            {
                echo 'Could not insert URL into the database. The database may be down.';
                return false;
            }
        }
    }

    function collision_detection( )
    {
        if( $this->is_collision( $this->temp_short_URL ) )
        {
            $this->resolve_collision( );
        }
    }
    
    function print_recent()
    {
        $query = 'SELECT short_URL FROM redirects WHERE 1 ORDER BY id DESC LIMIT 5';
        
        $result = mysql_query( $query , $this->db ) 
            or die( "Query failed : " . mysql_error());
        
        while( $row = mysql_fetch_array( $result ) )
        {
            echo '<a href="' . $this->basedir . '/' . $row['short_URL'] . '">' . $this->basedir . '/' . $row['short_URL'] . '</a><br/>';
        }
    }
    /*
    Our hash function returned a collision.
    Collision Resolution is as follows:
    1. start by replacing the last letter in the string with g
    2. check to see if there is still a collision, if there is, go to three otherwise we are done.
    3. increment our replacement letter. if it is the last single letter availible ('z'), go to 4, otherwise go to 5.
    4. decrement our replacement position
    5. replace the letter at our replacement postion. go to 2.
    */
    function resolve_collision(  )
    {
        $replacement_letter = 'g';
        $replacement_pos = -1;
        $replacement_length = 1;
        $new_short_URL = substr_replace( $this->temp_short_URL, $replacement_letter, $replacement_pos, $replacement_length );
        //echo $this->temp_short_URL . ' => ' . $new_short_URL . '<br />';
        while( $this->is_collision( $new_short_URL ) )
        {
            $replacement_letter++;
            if( $replacement_letter == 'z' )
            {
                $replacement_pos--;
                if( $replacement_pos == -8 )
                {
                    die( 'Collision Resolution Failed! Contact <a href"mailto:bkoranda@acs.carleton.edu">Brian Koranda</a>' );
                }
                else
                {
                    $replacement_letter = 'g';
                }
            }
            $new_short_URL = substr_replace( $this->temp_short_URL, $replacement_letter, $replacement_pos, $replacement_length );
            //echo $this->temp_short_URL . ' => ' . $new_short_URL . '<br />';
        }
        
        $this->temp_short_URL = $new_short_URL;
    }

    /*
    Lookup long URLs based on a given short URL. 
    If there is one, we redirect to that long URL.
    If there is less than one, we throw an error and redirect to /
    If there is more than one, we have a problem, but we just throw an error and redirect to /
    */
    function redirect( $site )
    {
        $query = 'SELECT long_URL FROM redirects WHERE short_URL="' . mysql_real_escape_string($site) . '"';
    
        $results = mysql_query( $query, $this->db )
            or die( "Query failed : " . mysql_error());
        
        $num_results = mysql_num_rows( $results );
        
        if( $num_results < 1 )
        {
            header( 'Location: ' . $this->basedir . '?error=URL+not+found!' );
        }
        elseif( $num_results > 1 )
        {
            header( 'Location: ' . $this->basedir . '?error=Multiple+short+URLs!' );
        }
        else
        {
            $row = mysql_fetch_row( $results );
            header( 'Location: ' . $row[0] );
        }
    }
    
    function is_admin()
    {
        return $this->admin;
    }
    
    function print_form( $shortURLerror = false, $longURLerror = false )
    {
        echo    '<p><form id="update_go_redirect_form" method="POST" action="?action=add">';
                
        if( $longURLerror )
        {
            echo    $_POST['longURL'] . ' already has the following short URLs associated with it:';                 

            $URLs = $this->get_short_URLs();
            echo '<ul>';
            foreach( $URLs as $URL )
            {
                echo    '<li><a href="' . $this->basedir . '/' . $URL . '">' . $this->basedir . '/' . $URL . '</a></li>' . "\n";
            }
            echo    '</ul>If you still wish to use <b>' . $_POST['shortURL'] . '</b> as the short URL, click "Shorten URL." Otherwise, click <a href="http://webapps.acs.carleton.edu/go/netid_only/">here</a> to add another short URL</p>';
            
            echo    '<input type="hidden" name="longURL" value="' . $_POST['longURL'] . '"/>' . "\n" .
                    '<input type="hidden" name="force" value="true" />' . "\n" .
                    '<input type="hidden" name="shortURL" value="' . $_POST['shortURL'] . '"/>';
            

        }
        else
        {
            echo    '<p>Enter the full URL (including "http://") you would like shortened:</p>';

            if( $shortURLerror )
            {
                echo    '<p><input type="text" size="80" name="longURL" value="' . $_POST['longURL'] . '" /></p>' . 
                        '<p>'.$_POST['shortURL'] . ' already exists.</p>';

				if ($this->is_admin()) {
					$existing_mapping = $this->get_long_URL_for_shortcut($_POST['shortURL']);
					$inlineJs = <<<JS
						<script language="JavaScript">
							function executeReplacement() {
								var shortcut = '${_POST["shortURL"]}';
								var url = '${_POST["longURL"]}';

								var form = $("form#update_go_redirect_form");
								var shortcutInputField = form.find("input[name='shortURL']");
								var urlInputField = form.find("input[name='longURL']");

								shortcutInputField.val(shortcut);
								urlInputField.val(url);
								form.attr("action", "?action=replace");
								form.submit();
							}
						</script>
JS;
					echo $inlineJs;
					echo "<div style='border-style:solid; width:75%; padding:10px'>\"" . $_POST['shortURL'] . "\" is currently mapped to \"$existing_mapping\"." .
						"<p>Would you like to delete the existing mapping for \"" . $_POST['shortURL'] . "\" " .
						"and replace it with one for \"" . $_POST['longURL'] . "\"?</p>" .
						"<p><input type=\"button\" onClick=\"executeReplacement();\" value=\"Yes; replace existing entry\"/></p></div>";
				}
            }
            else
            {
                echo    '<p><input type="text" size=80 name="longURL" /></p>';
            }
                
            if( $this->is_admin() )
            {
                echo    '<p>Type in the desired code to be assigned to this URL<br />
                        <b>Note:</b> If this field is left blank, a short URL will be generated automatically</p>
                        <p><input type="text" size="12" maxlength="12" name="shortURL" value="" />
                        <input type="hidden" name="force" value="false" /></p>';
            }
        }
        
        if( $this->is_admin() )
        {
            echo '<input type="hidden" name="method" value="admin" />';
        }
        else
        {
            echo '<input type="hidden" name="method" value="auto" />';
        }
        
        echo '<input type="submit" value="Shorten URL"/></form>';
        
    }
    
	function replace_entry()
	{
        if( $this->is_admin() )
		{
			$shortcut = $_POST['shortURL'];
			$url = $_POST['longURL'];

            $this->set_long_URL( $_POST['longURL'] );
			$this->set_temp_short_URL( $_POST['shortURL'] );

			if( $this->short_URL_exists() )
			{
				$query =    'UPDATE redirects SET ' . 
							'long_URL="' . mysql_real_escape_string($this->long_URL) . '" ' .
							'WHERE short_URL="' . mysql_real_escape_string($this->temp_short_URL) . '"';
				
				// echo $query . '<br />';
				
				$result = mysql_query( $query , $this->db ) 
					or die( "Query failed : " . mysql_error());

                array_push( $this->short_URLs, $this->temp_short_URL);
				$this->show_results_and_form();
			}
			else
			{
				echo "<p>Error attempting this action</p>"; // should never happen, so cryptic...
			}
		}
		else
		{
			# somehow a non-admin managed to submit this...
			echo '<p>Replace action not allowed for this user.</p>';
		}
	}

	function show_results_and_form()
	{
		$URLs = $this->get_short_URLs();
	
		foreach( $URLs as $URL )
		{       
			echo '<p>Shortened URL: <a href="' . $this->basedir . '/' . urlencode($URL) . '">http://go.carleton.edu/' . htmlspecialchars($URL) . '</a></p>';
		}
		
		$this->print_form();
	}
    
    function open_body()
    {
        echo '<body>';
    }
   
    function print_header()
    {
    ?><html>
        <head>
            <title><?php echo FULL_ORGANIZATION_NAME; ?>: Go</title>
			<?php
			if(defined('UNIVERSAL_CSS_PATH') && UNIVERSAL_CSS_PATH != '')
			{
				echo '<link rel="stylesheet" type="text/css" HREF="'.UNIVERSAL_CSS_PATH.'">'."\n";
			}
			?>
        </head>
    <?php
    }
    
    function print_footer()
    {
        echo '</body></html>';
    }
    
    /*
    prints out the results of the form submission
    
    if we don't have a longURL in our post, what's the point?
    if the a shortURL hasn't been specified or, has been specified but is
        just whitespace, (which would happen if either
        a non-admin is trying to add a link or an admin is adding one but 
        doesn't wish to specify a shortURL), then one generated.
    else, we check the following:
        if the specified short URL is already in the database (bad, redo)
        if a long URL is already in the database, then the admin can either
            add another short URL associated with that long URL (via the hidden 
            field, force) or use one of the other short URLs
    */
    function print_results( )
    {
        //$method = $_POST['method'] ;
        
        if (isset( $_POST['method']) ) $this->set_created_method( $_POST['method'] );
        
        if( isset( $_POST['longURL'] ) )
        {
            $this->set_long_URL( $_POST['longURL'] );
            
            if( isset( $_POST['shortURL'] ) && trim( $_POST['shortURL'] ) != '' )
            {
                $this->set_temp_short_URL( $_POST['shortURL'] );
                
                if( $this->short_URL_exists() )
                {
                    $this->print_form( true, false );
                }
                elseif( $this->long_URL_exists() && $_POST['force'] == 'false' )
                {
                    $this->print_form( false, true );
                }
                else
                {
                    if( $this->set_short_URL() )
                    {
						$this->show_results_and_form(); 
                    }
                    else
                    {
                        $this->print_form( true, false );
                    }
                }
            }
            else
            {
                if( !( $this->long_URL_exists() ) )
                {
                    $this->long_to_short();
                }
                    
                $URLs = $this->get_short_URLs();
                
                foreach( $URLs as $URL )
                {
                    echo '<p>Shortened URL: <a href="' . $this->basedir . '/' . urlencode($URL) . '">http://go.carleton.edu/' . htmlspecialchars($URL) . '</a></p>';
                }
                
                $this->print_form();
            }
        }
    }

    /*
    Splits $str into $num chunks and returns those chunks as an array
    */
    
    function str_split($str,$num = '1') 
    {
        if( $num < 1 ) 
            return false;
        
        $arr = array();
        
        for ($j = 0; $j < strlen($str); $j= $j+$num) 
        {
            $arr[] = substr($str,$j,$num);
        }

        return $arr;
    }
}
?>
