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
	reason_include_once( 'classes/page_types.php' );
	$rpts =& get_reason_page_types();
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
	foreach($rpts->get_page_type_names() as $name)
	{
		if($chr != strtoupper($name{0}) && !in_array(strtoupper($name{0}), $alphabet)){
			$chr = strtoupper($name{0});
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
	$pts = $rpts->get_page_types();
	$default_pt = $rpts->get_page_type('default');
	natksort($pts);
	$chr1 = 1;
	foreach($pts as $page_type)
	{
		$page_type_name = $page_type->get_name();
		if($chr1 != strtolower($page_type_name{0}))
		{
			if($chr1 != 1)
			{
				echo $top_link;
			}
			$chr1 = strtolower($page_type_name{0});
			echo '<h2><a name = "'.$chr1.'">'.strtoupper($chr1).'</a></h2>';
		}
		echo '<h3><a name="'.$page_type_name.'">'.prettify_string($page_type_name).'</a></h3>';
		echo '<ul>';
		foreach ($page_type->get_region_names() as $region)
		{
			$region_info = $page_type->get_region($region);
			$default_region_info = $default_pt->get_region($region);
			// (If the page is not default, then (if a region def differs from the default, then show it))
			// If the page is default, show all region defs.
			if (($page_type_name != 'default' && ($region_info['module_name'] != $default_region_info['module_name'] || $region_info['module_params'] != $default_region_info['module_params'])) || $page_type_name == 'default')
			{
			$xtra = '';
			if(isset($GLOBALS['_reason_deprecated_modules']) && @in_array($region_info['module_name'],$GLOBALS['_reason_deprecated_modules']))
				$xtra = ' (deprecated)';
			
				echo '<li>'.prettify_string($region).': '.(!empty($region_info['module_name']) ? str_replace('_',' ',"<strong>".$region_info['module_name'])."</strong>".$xtra."</li>" : "[empty]</li>");
			}
			if (!empty($region_info['module_params']))
			{
				echo "Parameters: <ul>";
				foreach ($region_info['module_params'] as $param => $value)
				{
					if (!empty($value))
					{
						echo "<li>".$param.": ";
						if (is_array($value))
						{
							pray($value);
						} else {
							echo $value;
						}
						echo("</li>");
					}
				}
				echo "</ul>";
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
