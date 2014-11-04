<?php
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'VirtualTour';
	
	class VirtualTour extends DefaultMinisiteModule
	{
		function init( $args = array() )
		{
			$head_items = $this->get_head_items();
			$head_items->add_javascript('/reason/local/luther_2014/javascripts/vendor/PTGuiViewer.js');
		}
		
		function run()
		{
			$site_id = $this->site_id;
			$es = new entity_selector( $site_id );
			$es->add_type( id_of( 'virtual_tour_type' ) );
			$es->add_right_relationship($this->cur_page->id(), relationship_id_of('page_to_virtual_tour'));
			$es->add_rel_sort_field($this->cur_page->id(), relationship_id_of('page_to_virtual_tour'));
			$es->set_order('rel_sort_order'); 
			$posts = $es->run_one();

			$number_tours = count($posts);
			if ($number_tours > 1){
				echo "<ul class='virtual-tour-list'> \n";
			}
			foreach( $posts AS $post )
			{				
				// uncompress zip file
				$this->uncompress_file($post->_id, $post->get_value('file_type'));
				
				if ($number_tours == 1)
				{
					echo "<h3>" . $post->get_value('name') . "</h3>" . "\n";
					echo "<div id=\"virtual-tour\">\n";
					$this->virtual_tour_javascript($post->_id);
					echo "</div>   <!-- id=\"virtual-tour\"-->\n";
				}
				elseif ($number_tours > 1)
				{
					echo "<li class='virtual-tour-container'>";
					echo "<h4>" . $post->get_value('name') . "</h4>" . "\n";
					echo "</li>   <!-- class=\"virtual-tour-container\"-->\n";
				}
			}
			if ($number_tours > 1)
			{
				echo "</ul>";
			}			
		}
		
		function has_content()
		{
			$site_id = $this->site_id;
			$es = new entity_selector( $site_id );
			$es->add_type( id_of( 'virtual_tour_type' ) );
			$es->add_right_relationship($this->cur_page->id(), relationship_id_of('page_to_virtual_tour'));
			$es->add_rel_sort_field($this->cur_page->id(), relationship_id_of('page_to_virtual_tour'));
			$es->set_order('rel_sort_order'); 
			$posts = $es->run_one();
			if (count($posts) > 0)
			{
				return true;
			}
			return false;
		}
		
		function uncompress_file($id, $suffix)
		{
			if (!is_dir(REASON_PATH . 'data/images/virtual_tours/' . $id))
			{
				$zip = new ZipArchive();
				$res = $zip->open(ASSET_PATH . $id . '.' . $suffix);
				if ($res === TRUE)
				{
					$zip->extractTo(REASON_PATH . 'data/images/virtual_tours/' . $id);
					$zip->close();
					$this->rename_tour_jpegs($id);
					unlink(ASSET_PATH . $id . '.' . $suffix);
				}
				else
				{
					trigger_error('could not unzip virtual tour: ' . ASSET_PATH . $id . '.' . $suffix);	
				}		
			}
		}
		
		function rename_tour_jpegs($id)
		{
			if (is_dir(REASON_PATH . 'data/images/virtual_tours/' . $id))
			{
				$files = glob(REASON_PATH . 'data/images/virtual_tours/' . $id . '/*.jpg', GLOB_MARK);
				foreach ($files as $file)
				{
					$last = substr($file, -7);
					rename($file, REASON_PATH . 'data/images/virtual_tours/' . $id . '/' . $id . $last);
				}
			}
		}
		
		function virtual_tour_javascript($id)
		{
			echo '
			<script type="text/javascript">
			//<![CDATA[
			
			// create a new viewer object:
			var viewer=new PTGuiViewer();
			
			// point to the location of the flash viewer (PTGuiViewer.swf)
			// this should be relative to the location of the current HTML document
			viewer.setSwfUrl("/reason/local/luther_2014/javascripts/vendor/PTGuiViewer.swf");
			
			// What to do if both Flash and the native viewer can be used:
			// use viewer.preferHtmlViewer() if you prefer to use the native HTML viewer
			// use viewer.preferFlashViewer() if you prefer to use the native HTML viewer
			// when Flash is available.
			viewer.preferHtmlViewer();
			// viewer.preferFlashViewer();
			
			// set parameters for the viewer:
			viewer.setVars({
				pano: "/reason/images/virtual_tours/' . $id . '/' . $id . '_",
				format: "14faces",
				pan: 0,
				minpan: -180,
				maxpan: 180,
				tilt:0,
				mintilt: -83.23424494649227,
				maxtilt: 83.23424494649227,
				fov: 90,
				minfov: 10,
				maxfov: 120,
				autorotatespeed: 0,
				autorotatedelay: 0,
				maxiosdimension: 1134,
				showfullscreenbutton_flash: 1,
				showfullscreenbutton_html: 1,
				enablegyroscope: 1
			});
			
			// and embed the viewer
			// The remainder of this HTML document should contain an element with the id mentioned here
			// (e.g. <div id="..."> )
			// The viewer will be embedded as a child of that element
			
			viewer.embed("' . $id  . '-panoviewer");
			
			//]]>
			</script>
			';
			echo '<div id="' . $id . '-panoviewer"></div>';
		}
		
	}
?>
