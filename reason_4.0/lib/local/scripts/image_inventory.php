<?php
/**
 * Inventory of all minisite images
 * Images must be present on server
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
	reason_include_once( 'classes/sized_image.php' );
	reason_include_once('function_libraries/image_tools.php');
	reason_include_once('classes/admin/modules/default.php');
	$top_link = '<a href="#top" class="top">Return to top</a>';

	reason_include_once( 'function_libraries/user_functions.php' );
        force_secure_if_available();
        $current_user = check_authentication();
	if (!reason_user_has_privs( get_user_id ( $current_user ), 'assign_any_page_type') )
	{
		die('<h1>Sorry.</h1><p>You do not have permission to view page type info.</p></body></html>');
	}

	echo '<!DOCTYPE html><head>';
	echo '<title>Reason: Image Inventory</title>';
	?>
	<link rel="stylesheet" type="text/css" href="/reason_package/css/universal.css" />
	<link rel="stylesheet" type="text/css" href="/reason/css/simplicity/blue.css" />
	<link rel="stylesheet" type="text/css" href="/reason/css/forms/form.css" />
	<script type="text/javascript" src="/jquery/jquery_latest.js"></script>
	<style type="text/css">
	  body {background-color: white;}
	  div#wrapper {border: none; width: 90%;}
	  h4 {margin-bottom: 0px;}
	  table.table_data {border-left: 1px solid #000000;}
	  
	</style>
	
	<?php
	echo '</head><body><div id="wrapper">';
	echo '<h1>Reason Image Inventory</h1>';
	$site_id = filter_input(INPUT_GET, "site", FILTER_SANITIZE_STRING);
	
	function put_image_params($filename)
	// output original and thumbnail image size, width, and height to table if they exist
	{
		if (file_exists($filename))
		{		
			$fsize = filesize($filename);
			$img_size = round($fsize/1000);
			echo '<td>' . $img_size . 'k</td>' . "\n";
			$info = getimagesize($filename);
			echo '<td>' . $info[0] . '</td>' . "\n";  // width
			echo '<td>' . $info[1] . '</td>' . "\n";  // height						
		}
		else
		{
			$img_size = 0;
			for ($i = 0; $i < 3; $i++)
			{
				echo '<td>&nbsp;</td>' . "\n";
			}
		}
		return $img_size;
	}
		
	function list_sites()
	{
		$es = new entity_selector();
		$es->add_type(id_of('site'));
		$es->set_order('entity.name ASC');
		$es->limit_tables();
		$sites = $es->run_one();
		
		foreach($sites as $id => $site)
		{
			echo "<a href=\"/reason/scripts/developer_tools/image_inventory.php?site=" . $site->get_value('id'). "\"><h4>" . $site->get_value('name') . "</h4></a>";
		}		
	}
	
	function show_site_images($site_id)
	{
		$site = mysql_query('SELECT * FROM entity WHERE id = ' . $site_id);
		$site = mysql_fetch_assoc($site);
		
		$es = new entity_selector($site_id);
		$es->add_type( id_of('image') );
		$result = $es->run_one();		
		$num_site_images = count($result);
		$total_size = 0;

		echo "<h4>" . $site['name'] . " (". $num_site_images . ")</h4>";
		echo '<table class="table_data"><tbody><tr>
			<th>id</th>
			<th>name</th>
			<th>created by</th>
			<th>type</th>
			<th>top?</th>
			<th>original</th>
			<th>width</th>
			<th>height</th>
			<th>default</th>
			<th>width</th>
			<th>height</th>
			<th>thumbnail</th>
			<th>width</th>
			<th>height</th>	
			</tr>'."\n";
		
		$i = 1;
		foreach ($result as $image)
		{
			$class = ($i++ % 2 == 0) ? 'even' : 'odd';
			echo '<tr class = "' . $class .'"><td><a href="/reason/admin/?entity_id_test=' . $image->get_value('id'). '&cur_module=EntityInfo">' . $image->get_value('id') . '</a></td>' . "\n";
			echo '<td>' . $image->get_value('name') . '</td>' . "\n";
			echo '<td><a href="/reason/admin/?entity_id_test=' . $image->get_value('created_by'). '&cur_module=EntityInfo">' . $image->get_value('created_by') . '</a></td>' . "\n";
			echo '<td>' . $image->get_value('image_type') . '</td>' . "\n";
			if (preg_match("/imagetop/", $image->get_value('keywords')))
				echo '<td>&#x2713;</td>' . "\n";
			else
				echo '<td>&nbsp;</td>' . "\n";
			$total_size += put_image_params(PHOTOSTOCK . $image->get_value('id') . '_orig.' . $image->get_value('image_type'));
			$total_size += put_image_params(PHOTOSTOCK . $image->get_value('id') . '.' . $image->get_value('image_type'));
			$total_size += put_image_params(PHOTOSTOCK . $image->get_value('id') . '_tn.' . $image->get_value('image_type'));
			echo '</tr>';
		}
		echo '</tbody></table>'."\n";
		
		echo '<h4>Total size = ' . number_format($total_size) .' k</h4>' . "\n";
	}
	
	if ($site_id)
	{
		show_site_images($site_id);
	}
	else
	{
		list_sites();
	}
	
	
		
	echo '</div></body></html>';

	
?>