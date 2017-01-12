<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'ExternalUrl';
	/**
	 * A content manager for rss feeds
	 */
	require_once(MAGPIERSS_INC . 'rss_fetch.inc');
	class ExternalUrl extends ContentManager
	{
		function alter_data()
		{
			$this->set_display_name('num_posts', 'Number of Posts');
			$this->set_comments('num_posts', form_comment('Number of posts to show. If set to 0, all items will be shown.'));
			$this->set_display_name('field_title', 'Filter Field');
			$this->set_comments('field_title',form_comment('Title of field to filter by.'));
			$this->set_display_name('field_words', 'Filter Keywords');
			$this->set_comments('field_words', form_comment('A comma separated list of keywords to filter by.'));
			$this->set_display_name('future_posts','Show posts with future date?');
			$this->set_order(
				array(
					'name',
					'unique_name',
					'url',
					'num_posts',
					'field_title',
					'field_words',
					'future_posts',
				)
			);
			//dynamically set filter field options based on feed
			$this->change_element_type('field_title','select_no_sort');
			$options = ['title'=>'title', 'description'=>'description', 'Any'=>'Any'];
			$url = htmlspecialchars_decode($this->get_value('url'));
			if($url != ""){
				$rss = fetch_rss($url);
				if(!empty($rss) && $rss && !$rss->ERROR && $rss != false)
				{
					//gather options for filter fields
					$newOptionsRaw = array_keys($rss->items[0]);
					$newOptions = [];
					foreach($newOptionsRaw as $newOption){
						if(!in_array($newOption,$options) && substr($newOption,-1) != "#"){
							$options[$newOption]=$newOption;
						}
					}
					$rssItems = $rss->items;
					$rssNewItems = [];
					for($x=0;$x<count($rssItems);$x+=1){
						$item = $rssItems[$x];
						$filter_match = true;
						if($this->get_value('field_title') != "" && $this->get_value('field_words')!=""){
							$filter_match = false;
							$fieldTitle = strtolower($this->get_value('field_title'));
							$fieldWordsStr = $this->get_value('field_words');
							$fieldWords = explode(",",$fieldWordsStr);
							if($fieldTitle == "any"){
								$filterOptions = $options;
								unset($filterOptions["Any"]);
								foreach($filterOptions as $currTitle){
									$sub_match = false;
									for($b=0;$b<count($fieldWords);$b+=1){
										$fieldWords[$b] = preg_quote(trim($fieldWords[$b]));
										$currWord = $fieldWords[$b];
										if(isset($item[$currTitle]) && gettype($item[$currTitle]) == 'string' && preg_match('/'.$currWord.'/i' , $item[$currTitle]))
										{
											$sub_match = true;
										}
										else{
											$b += count($fieldWords);
											$sub_match = false;
										}
									}
									if($sub_match){
										$filter_match = true;
									}
								}
							}
							else{
								for($y=0;$y<count($fieldWords);$y+=1){
									$fieldWords[$y] = preg_quote(trim($fieldWords[$y]));
									$word = $fieldWords[$y];
									if(isset($item[$fieldTitle]) && preg_match('/'.$word.'/i' , $item[$fieldTitle]))
									{
										$filter_match = true;
									}
									else{
										$y += count($fieldWords);
										$filter_match = false;
									}
								}
							}
						}
						$now = time();              // now is the time, sans morris day
						$date = isset($item['date_timestamp']) ? $item['date_timestamp'] : "";
						//found a matching item
						if((($date - $now) < 0 || $this->get_value('future_posts') == "Yes") && $filter_match){
							foreach(array_keys($item) as $key){
								if(substr($key,-1) != "#"){
									$newItem[$key] = $item[$key];
								}
							}
							array_push($rssNewItems,$newItem);
						}
						if(count($rssNewItems) == 3){
							$x+=count($rssItems);
						}
					}
					echo('<script>var rssItems = JSON.parse(\'' . addslashes(json_encode($rssNewItems)) . '\');</script>');
				}
			}
			unset($options["Any"]);
			ksort($options);
			$options = array_merge(array("Any"=>"Any"),$options);
			$this->set_element_properties('field_title',['options'=>$options]);
		$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH .'content_managers/external_url.js');
		$this->head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/content_managers/external_url.css');
		}
	}
