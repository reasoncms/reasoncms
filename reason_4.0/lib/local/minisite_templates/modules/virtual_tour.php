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
			$i = 1;

			echo "<div id='tabs'> \n";
			echo "<div class='tabs-content'> \n";

			echo "<div id='virtual-tour'> \n";
			
			foreach( $posts AS $post )
			{
				if ($number_tours > 1)
				{
					if ($i == 1)
					{
						echo "<div class='content active' id='vtour". $i ."'> \n";
					}
					else 
					{
						echo "<div class='content' id='vtour". $i ."'> \n";
					}
				}	
				
				// uncompress zip file
				$this->uncompress_file($post->_id, $post->get_value('file_type'));
				$this->virtual_tour_javascript($post->_id);
				echo "<p>" . $post->get_value('description') . "</p> \n";
				if ($number_tours > 1)
				{
					echo "</div> \n";
				}
				
				$i++;				
			}
			
			echo "</div>   <!-- id='virtual-tour'-->\n";
			echo "</div>   <!-- class='tabs-content'--> \n";
			
			if ($number_tours > 1)
			{
				$i = 1;
				echo "<dl class='tabs' data-tab=''> \n";
				foreach( $posts AS $post )
				{	
					if ($i == 1)
					{
						echo "<dd class='tab-title vtour". $i ." active'> \n";
					}
					else
					{
						echo "<dd class='tab-title vtour". $i ."'> \n";
					}
					//echo "<a href='#vtour". $i ."'>". $post->get_value('name') ."</a></dd> \n";
					echo "<a href='#vtour". $i ."'><div class='crop'><img src='/reason/images/virtual_tours/". $post->_id ."/". $post->_id ."_07.jpg'></div><h5>". $post->get_value('name') ."</h5></a></dd> \n";
					$i++;
				}				
				echo "</dl> \n";
			}
			echo "</div>   <!-- id='tabs'--> \n";
			
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
