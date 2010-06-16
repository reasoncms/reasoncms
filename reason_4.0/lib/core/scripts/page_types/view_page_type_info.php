<?php
/**
 * A page that allows people who do not have acces to the code to see what modules go 
 * in which page types (and where, kinda)
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * include dependencies
 */
	include_once( 'reason_header.php' );
	reason_require_once( 'minisite_templates/page_types.php' );
	$page_types = $GLOBALS['_reason_page_types'];
	reason_include_once( 'classes/entity.php' );
	connectDB( REASON_DB );
	reason_include_once( 'function_libraries/images.php' );
	$top_link = '<a href="#top" class="top">Return to top</a>';

	reason_include_once( 'function_libraries/user_functions.php' );
        force_secure_if_available();
        $current_user = check_authentication();
	if (!reason_user_has_privs( get_user_id ( $current_user ), 'assign_any_page_type') )
	{
		die('<h1>Sorry.</h1><p>You do not have permission to view page type info.</p></body></html>');
	}

	echo '<html><head>';
	echo '<title>Reason: Page Type Information</title>';
	?>
	<style type="text/css">
		h2 {
			border:1px solid #748FC2;
			padding:.33em;
			background-color:#B2C3E3;
			color:#FFFFFF;
			font-size:medium;
		}
		body {
			padding:2em;
			font-family:Verdana, Arial, Helvetica, sans-serif;
			font-size:small;
			background-color:#FFFFFF;
		}
		h3 {
			margin-bottom:.5em;
			margin-top:1.33em;
			color:#507007;
		}
		ul {
			margin-top:.5em;
		}
		div#defaultPageLocations {
			float:right;
			background-color:#FFFFFF;
			padding:1em;
		}
	</style>
	<?php
	echo '</head><body>';
	echo '<h1>Reason Page Type Information</h1>';
	$img_id = id_of('default_page_locations_image');
	if(!empty($img_id))
	{
		$image = new entity($img_id);
		echo '<div id="defaultPageLocations">';
		show_image( $image );
		echo '</div>';
	}
	
	//generate alphabetical links at top of page
	$alphabet = array();	//non-repeating array of the first letters of the keys of $page_types
	$chr = 1;	
	foreach($page_types as $key=>$value)
	{
		if($chr != strtoupper($key{0}) && !in_array(strtoupper($key{0}), $alphabet)){
			$chr = strtoupper($key{0});
			$alphabet[] = $chr;
		}
	}
	sort($alphabet);
	foreach($alphabet as $letter)
	{
		$alphabet_links[] = '<a href = "#'.strtolower($letter).'"> '.$letter.'</a>'; 
	}
	echo '<em>Jump to:</em> '.implode(' | ',$alphabet_links);
	
	//display relevant $page_type values
	natksort($page_types);
	$chr1 = 1;
	foreach($page_types as $key=>$value)
	{
		if($chr1 != strtolower($key{0}))
		{
			if($chr1 != 1)
			{
				echo $top_link;
			}
			$chr1 = strtolower($key{0});
			echo '<h2><a name = "'.$chr1.'">'.strtoupper($chr1).'</a></h2>';
		}
		echo '<h3><a name="'.$key.'">'.prettify_string($key).'</a></h3>';
		echo '<ul>';
		foreach( $value as $key2=>$value2 )
		{
			if(is_array($value2))
			{
				$module_name = $value2['module'];
				$is_a_module = true;
			}
			elseif(empty($value2))
			{
				$module_name = '[empty]';
				$is_a_module = false;
			}
			else
			{
				$module_name = $value2;
				$is_a_module = true;
			}
			
			if($is_a_module)
			{
				$xtra = '';
				if(isset($GLOBALS['_reason_deprecated_modules']) && in_array($module_name,$GLOBALS['_reason_deprecated_modules']))
					$xtra = ' (deprecated)';
				$module_name = '<strong>'.$module_name.'</strong>'.$xtra;
			}
			echo '<li>'.prettify_string($key2).': '.str_replace('_',' ',$module_name).'</li>';
			
			if(is_array($value2))
			{
				echo 'Parameters: <ul>';
					while (list ($key3,$value3) = each ($value2))
					{
						if($key3 != 'module' && !empty($value3) )
						{
							echo '<li>'.$key3.': ';
							if(is_array($value3))
							{
								pray($value3);
							}
							else
							{
								echo $value3;
							}
							echo '</li>';
						}
					}
				echo '</ul>';
			}
		}
		echo '</ul>' ;
	}
	echo '</body></html>';

	/**
	* Performs a "natural" sort on the keys of an array.  Taken from PHP.net.
	* @author ssb45 at cornell dot edu
	*/
	function natksort(&$array) {
		$keys = array_keys($array);
		natcasesort($keys);
		foreach ($keys as $k) 
		{
			$new_array[$k] = $array[$k];
		}
		$array = $new_array;
		return true;
	}
?>
