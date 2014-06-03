<?php
/**
 * Inventory of all minisite images
 * Images must be present on server
 * Written by: jonebr01@luther.edu
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
	<style type="text/css">
	  body {background-color: white;}
	  div#wrapper {border: none; width: 90%;}
	  h4 {margin-bottom: 0px;}
	  table.table_data {border-left: 1px solid #000000;}
	  
	</style>
	
	<?php
	echo '</head><body><div id="wrapper">';
	echo '<h1>Reason Image Inventory</h1>';
	
	/*$img_id = id_of('default_page_locations_image');
	if(!empty($img_id))
	{
		$image = new entity($img_id);
		echo '<div id="defaultPageLocations">';
		show_image( $image );
		echo '</div>';
	}*/
	
	function put_image_params($filename)
	// output original and thumbnail image size, width, and height to table if they exist
	{
		if (file_exists($filename))
		{
			$fsize = filesize($filename);
			echo '<td>' . round($fsize/1024) . 'k</td>' . "\n";
			$info = getimagesize($filename);
			echo '<td>' . $info[0] . '</td>' . "\n";  // width
			echo '<td>' . $info[1] . '</td>' . "\n";  // height						
		}
		else
		{
			for ($i = 0; $i < 3; $i++)
			{
				echo '<td>&nbsp;</td>' . "\n";
			}
		}
	}
	
	$total_images = 0;
	
	$es = new entity_selector();
	$es->add_type(id_of('site'));
	$es->set_order('entity.name ASC');
	$es->limit_tables();
	$sites = $es->run_one();
		
	foreach($sites as $id => $site)
	{
		$es = new entity_selector($id);
		$es->add_type( id_of('image') );
		$result = $es->run_one();
		
		$num_site_images = count($result);
		$total_images += $num_site_images;
		echo "<h4>" . $site->get_value('name') . " (". $num_site_images . ")</h4>";
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
			echo '<td>' . $image->get_value('created_by') . '</td>' . "\n";
			echo '<td>' . $image->get_value('image_type') . '</td>' . "\n";
			if (preg_match("/imagetop/", $image->get_value('keywords')))
				echo '<td>&#x2713;</td>' . "\n";
			else
				echo '<td>&nbsp;</td>' . "\n";
			put_image_params(PHOTOSTOCK . $image->get_value('id') . '_orig.' . $image->get_value('image_type'));			
			echo '<td>' . $image->get_value('size') . 'k</td>' . "\n";
			echo '<td>' . $image->get_value('width') . '</td>' . "\n";
			echo '<td>' . $image->get_value('height') . '</td>' . "\n";
			put_image_params(PHOTOSTOCK . $image->get_value('id') . '_tn.' . $image->get_value('image_type'));
			echo '</tr>';
		}
		echo '</tbody></table>'."\n";
	}
	
	echo '<h4>Total images = ' . $total_images .'</h4>' . "\n";
	
	echo '</div></body></html>';

	
?>
