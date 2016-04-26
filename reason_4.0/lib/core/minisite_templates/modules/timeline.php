<?php
	include_once('reason_header.php');
	reason_include_once( 'minisite_templates/modules/default.php' );
	reason_include_once('classes/media/factory.php');
	include_once( CARL_UTIL_INC . 'basic/cleanup_funcs.php' );
	//include_once( DISCO_INC . 'disco.php' );
	reason_include_once( 'classes/api/api.php' );
	//reason_include_once( 'classes/timeliner.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'TimelineModule';

	class TimelineModule extends DefaultMinisiteModule
	{
		var $timeline;
		var $category_id = 0;
		var $category_id_list = array(0 => "All");
		var $cleanup_rules = array('category_id' => 'turn_into_int');
		
		static function setup_supported_apis()
		{
			$timeline_api = new ReasonAPI(array('json', 'html'));
			self::add_api('timeline', $timeline_api);
		}
		
		function init( $args = array() )
		{
			parent::init($args);
			$head_items = $this->get_head_items();
			$head_items->add_javascript('//cdn.knightlab.com/libs/timeline3/latest/js/timeline.js');
			$head_items->add_stylesheet('//cdn.knightlab.com/libs/timeline3/latest/css/timeline.css');
			$head_items->add_stylesheet('//cdn.knightlab.com/libs/timeline3/latest/css/fonts/font.abril-droidsans.css');
				
			if (!empty($this->request['category_id']))
				$this->category_id = $this->request['category_id'];
		}

		function has_content()
		{
			$site_id = $this->site_id;
			$es = new entity_selector($site_id);
			$es->add_type( id_of('timeline_type'));
			$es->add_right_relationship($this->cur_page->id(), relationship_id_of('page_to_timeline'));
			$es->add_rel_sort_field($this->cur_page->id(), relationship_id_of('page_to_timeline'));
			$es->set_order('rel_sort_order');
			$posts = $es->run_one();
			if (count($posts) > 0)
			{
				return true;
			}

			return false;
		}
		
		/**
		 * @todo move the form into an actual disco form
		 * @todo make the form work with JS off (e.g. include submit button)
		 * @todo improve accessibility of form (e.g. use jquery onchange)
		 */
		function create_category_dropdown()
		// Any timeline item associated with a category will have been added to the $category_id_list in the function create_timeline_slide()
		// The first item on the list is -- none --
		{
			if (count($this->category_id_list) == 1)   // no slides have an associated category
				return;

			asort($this->category_id_list);
			
			$ret = '';
			$ret .= '
				<script type="text/javascript">
					$(document).ready(function()
					{	
						$("select").val("' . strval($this->category_id) . '");
					});
				</script>'."\n";

			$ret .= '<form method="get" name="disco_form">'."\n";
			$ret .= '<div id="discoLinear">'."\n";
			
			$ret .= '<span id="category_label">Tags: </span>'."\n";
			$ret .= '<select name="category_id" id="category_id" style="height: auto; width: auto; margin-bottom: 1.875rem;" title="filter by category" onchange="this.form.submit();">'."\n";
			foreach ($this->category_id_list as $key => $value)
			{
				if ($key == $this->category_id)
				{
					$ret .= '<option value="' . strval($key) . '" selected="selected">' . strval($value) .'</option>'."\n";
				}
				else
				{
					$ret .= '<option value="' . strval($key) . '">' . strval($value) .'</option>'."\n";
				}
			}
			$ret .= '</select>'."\n";
			$ret .= '</div>'."\n";
			
			$ret .= '</form>'."\n";
			echo $ret;
		}

		function create_date_object($date_string)
		{
			if (!$this->nullify($date_string))
			{
				return null;
			}

			return [
				'year'  => intval(date('Y', strtotime($date_string))),
				'month' => intval(date('m', strtotime($date_string))),
				'day'   => intval(date('d', strtotime($date_string)))
			];
		}

		function nullify($val)
		{
			if (empty($val) || preg_match('/^\s+$/', $val) || preg_match('/^0000\-00\-00/', $val))
			{
				return null;
			}

			return $val;
		}
		
		function create_timeline_slide($timeline_item, &$timeline_item_json)
		// populate the timeline_item_json array with contents of the timeline_item
		// returns false if timeline_item is not in the currently chosen category otherwise true
		{
			$add_timeline_event = true;
			
			$timeline_item_json = [
				'start_date' => $this->create_date_object($timeline_item->get_value('start_date')),
				'end_date'   => $this->create_date_object($timeline_item->get_value('end_date')),
				'display_date' => $this->nullify($timeline_item->get_value('display_date')),
				'unique_id'    => $this->nullify($timeline_item->get_value('unique_name')),
				'text' => [
					'headline' => $timeline_item->get_value('name'),
					'text'     => $timeline_item->get_value('text')
				]
			];
			
			if ($timeline_item->get_value('media') == 'reason_image')
			{
				$es = new entity_selector();
				$es->add_type(id_of('image'));
				$es->add_right_relationship($timeline_item->_id, relationship_id_of('timeline_item_to_image'));
				$es->set_num(1);
				$images = $es->run_one();
			
				if (!empty($images))
				{
					$image = reset($images);
			
					$timeline_item_json['media'] = [
					'url'     => WEB_PHOTOSTOCK . $image->_id . '.' . $image->get_value('image_type'),
					'caption' => $image->get_value('description')
					];
				}
			}
			else if ($timeline_item->get_value('media') == 'reason_media_work')
			{
				$es = new entity_selector();
				$es->add_type(id_of('av'));
				$es->add_right_relationship($timeline_item->_id, relationship_id_of('timeline_item_to_media_work'));
				$es->set_num(1);
				$media_works = $es->run_one();
			
				if (!empty($media_works))
				{
					$media_work = reset($media_works);
					$displayer = MediaWorkFactory::media_work_displayer($media_work);
					if ($displayer)
					{
						$displayer->set_media_work($media_work);
						if ($media_work->get_value('integration_library') == 'youtube' && !empty($media_work->get_value('entry_id')))
						{
							$timeline_item_json['media'] = [
								'url' => 'https://www.youtube.com/watch?v=' . $media_work->get_value('entry_id')
							];
						}
						else if ($media_work->get_value('integration_library') == 'vimeo' && !empty($media_work->get_value('entry_id')))
						{
							$timeline_item_json['media'] = [
								'url' => 'https://vimeo.com/' . $media_work->get_value('entry_id')
							];
						}
						else
						{
							$timeline_item_json['media'] = [
								'url' => '<iframe style="height: 425px; width: 356px; border: 0" src="' . $displayer->get_iframe_src(405, 356, $media_work) . '&amp;autostart=0"></iframe>'
							];
						}				
					}
				}
			}
			else if ($timeline_item->get_value('media') == 'other')
			{
				$timeline_item_json['media'] = [
					'url' => $timeline_item->get_value('other_media')
				];
			}
			
			// Assign an attached category
			$es = new entity_selector();
			$es->add_type(id_of('category_type'));
			$es->add_right_relationship($timeline_item->_id, relationship_id_of('timeline_item_to_category'));
			$categories = $es->run_one();

			// if category is not yet in the category list then add it
			foreach ($categories as $category)
			{
				if (!array_key_exists($category->get_value('id'), $this->category_id_list))
					$this->category_id_list[$category->get_value('id')] = $category->get_value('name');
			}
			
			if ($this->category_id != 0 && (empty($categories) || !array_key_exists($this->category_id, $categories)))
				$add_timeline_event = false;
			
			$timeline_item_json = $this->customize_timeline_item_json($timeline_item, $timeline_item_json);

			// Remove any keys with null values from the timeline item json
			foreach ($timeline_item_json as $key => $value)
			{
				if ($value === null)
					unset($timeline_item_json[$key]);
			}
			
			return $add_timeline_event;					
		}
		/**
		 * Designed to be overloaded by a class extension
		 */
		function customize_timeline_item_json($timeline_item, $timeline_item_json)
		{
			return $timeline_item_json;
		}
	
		/**
		 * We run the api we setup in setup_supported_apis()
		 *
		 * Note that we ask for the content type and set the content differently for the json and html content types.
		 *
		 * If the content type is not 'json' or 'html', note that we run the api anyway, as it supports standard error cases.
		 */
		function run_api()
		{
			$api = $this->get_api();
			if ($api->get_name() == 'timeline')
			{
				if ($api->get_content_type() == 'json')
				{
					if ($timeline = $this->get_timeline())
						$api->set_content(json_encode($this->generate_json_data($timeline)));
					else
						$api->set_content(json_encode(array()));
				}
				if ($api->get_content_type() == 'html')
				{
					ob_start();
					$this->run();
					$html = ob_get_clean();
					$api->set_content($html);
				}
				$api->run();
			}
			else parent::run_api(); // support other apis defined by parents
		}
		
		function run()
		{
			if ($timeline = $this->get_timeline())
			{
				
				echo '<div id="timelineWrapper" class="'.$this->get_api_class_string().'">';
				$json = json_encode($this->generate_json_data($timeline));
				
				$this->create_category_dropdown();
				
				$timeline_dom_id = uniqid('timeline_');
				
				echo "
				<div id=\"$timeline_dom_id\" style=\"width: 100%; height: 600px\"></div>
				<script>
					if (!window.timelines) window.timelines = []
					timelines.push(new TL.Timeline('$timeline_dom_id', $json))
				</script>";
				echo '</div>'."\n";
			}
		}
		
		function get_timeline()
		{
			if(!isset($this->timeline))
			{
				$es = new entity_selector( $this->site_id );
				$es->add_type( id_of('timeline_type'));
				$es->add_right_relationship($this->cur_page->id(), relationship_id_of('page_to_timeline'));
				$es->add_rel_sort_field($this->cur_page->id(), relationship_id_of('page_to_timeline'));
				$es->set_order('rel_sort_order');
				$es->set_num(1);
				$timelines = $es->run_one();
			
				if (!empty($timelines))
					$this->timeline = reset($timelines);
				else
					$this->timeline = FALSE;
			}
			return $this->timeline;
		}
		
		function generate_json_data($timeline)
		{
			$json = [
				'title' => [],
				'events' => [],
				'eras' => [],
				'scale' => 'human'
			];
			
			$es = new entity_selector($this->site_id);
			$es->add_type(id_of('timeline_item_type'));
			$es->add_right_relationship($timeline->_id, relationship_id_of('timeline_to_title_timeline_item'));
			$timeline_titles = $es->run_one();
			
			if (!empty($timeline_titles))
			{
				$timeline_title = reset($timeline_titles);
				$this->create_timeline_slide($timeline_title, $timeline_item_json);
				$json['title'] = $timeline_item_json;
			}

			$es = new entity_selector($this->site_id);
			$es->add_type(id_of('timeline_item_type'));
			$es->add_right_relationship($timeline->_id, relationship_id_of('timeline_to_timeline_item'));
			$timeline_items = $es->run_one();

			foreach($timeline_items as $timeline_item)
			{
				if ($this->create_timeline_slide($timeline_item, $timeline_item_json))	
					$json['events'][] = $timeline_item_json;
			}
			
			return $json;
		}
	}
