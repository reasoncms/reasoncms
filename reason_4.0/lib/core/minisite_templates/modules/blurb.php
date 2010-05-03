<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 	/**
 	 * Include the base class & register module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'BlurbModule';
	
	/**
	 * A minisite module that displays text blurbs.
	 *
	 * By default, this module displays blurbs attached to the page by relationship order.
	 *
	 * Via parameters, you can make the module display a set number, randomize, and more.
	 */
	class BlurbModule extends DefaultMinisiteModule
	{
		var $acceptable_params = array(
		'blurb_unique_names_to_show' => '',
		'num_to_display' => '',
		'rand_flag' => false,
		'exclude_shown_blurbs' => true,
		'demote_headings' => 1, );
		var $es;
		var $blurbs = array();
		
		function init( $args = array() ) // {{{
		{
			parent::init( $args );
			if (!empty($this->params['blurb_unique_names_to_show']))
			{
				$this->build_blurbs_array_using_unique_names();
			}
			else
			{
				$this->es = new entity_selector();
				$this->es->description = 'Selcting blurbs for this page';
				$this->es->add_type( id_of('text_blurb') );
				$this->es->add_right_relationship( $this->parent->cur_page->id(), relationship_id_of('minisite_page_to_text_blurb') );
               	$this->es->add_rel_sort_field( $this->parent->cur_page->id(), relationship_id_of('minisite_page_to_text_blurb'), 'rel_sort_order');
				if ($this->params['rand_flag']) $this->es->set_order('rand()');
				else $this->es->set_order( 'rel_sort_order ASC' );
				if ($this->params['exclude_shown_blurbs'])
				{
					$already_displayed = $this->used_blurbs();
				}
				if (!empty($already_displayed))
				{
					$this->es->add_relation('entity.id NOT IN ('.join(',',$already_displayed).')');
				}
				if (!empty($this->params['num_to_display'])) $this->es->set_num($this->params['num_to_display']);
				$this->blurbs = $this->es->run_one();
			}
			$this->used_blurbs(array_keys($this->blurbs));
		} // }}}
		
		function build_blurbs_array_using_unique_names()
		{
			$blurb_array = array();
			$blurb_unique_name_array = (is_array($this->params['blurb_unique_names_to_show'])) 
									   ? $this->params['blurb_unique_names_to_show']
									   : array($this->params['blurb_unique_names_to_show']);
			
			if ($this->params['rand_flag'] == true) shuffle($blurb_unique_name_array);
			
			$max_count = (!empty($this->params['num_to_display'])) ? $this->params['num_to_display'] : count($blurb_unique_name_array);
			$count = 0;
			foreach($blurb_unique_name_array as $blurb_unique_name)
			{
				$blurb_id = id_of($blurb_unique_name, true, false);
				if(!$blurb_id)
				{
					trigger_error('Unable to find blurb with unique name '.$blurb_unique_name);
					continue;
				}
				if ($this->params['exclude_shown_blurbs'])
				{
					if (!isset($used_blurbs)) $used_blurbs = $this->used_blurbs();
					if (in_array($blurb_id, $used_blurbs)) continue; // it has been used do not add it to our array
				}
				$blurb_array[$blurb_id] = new entity($blurb_id);
				$count++;
				if ($count == $max_count) break;
			}
			$this->blurbs = $blurb_array;
		}
		
		function used_blurbs( $used = array() )
		{
			static $used_blurbs = array();
			$used_blurbs = array_merge($used_blurbs, $used);
			return $used_blurbs;
		}
				
		function has_content() // {{{
		{
			if( !empty($this->blurbs) )
				return true;
			else
				return false;
		} // }}}
		function run() // {{{
		{
			echo '<div class="blurbs">'."\n";
			$i = 0;
			foreach( $this->blurbs as $blurb )
			{
				$i++;
				echo '<div class="blurb number'.$i;
				if($blurb->get_value('unique_name'))
					echo ' uname_'.htmlspecialchars($blurb->get_value('unique_name'));
				echo '">';
				echo demote_headings($blurb->get_value('content'), $this->params['demote_headings']);
				echo '</div>'."\n";
			}
			echo '</div>'."\n";
		} // }}}
		
		/**
		*  Template calls this function to figure out the most recently last modified item on page
		* This function uses the most recently modified blurb
		* @return mixed last modified value or false
		*/
		function last_modified() // {{{
		{
			if(!empty($this->blurbs))
			{
				$max = '0000-00-00 00:00:00';
				foreach(array_keys($this->blurbs) as $key)
				{
					if($this->blurbs[$key]->get_value('last_modified') > $max)
						$max = $this->blurbs[$key]->get_value('last_modified');
				}
				if($max != '0000-00-00 00:00:00')
					return $max;
			}
			return false;
		} // }}}
		
		/**
		 * Provides (x)HTML documentation of the module
		 * @return mixed null if no documentation available, string if available
		 */
		function get_documentation()
		{
			if(!empty($this->params['num_to_display']))
				$num = $this->params['num_to_display'];
			else
				$num = 'all';
			
			$ret = '<p>Displays '.$num.' blurbs attached to this page';
			if($this->params['rand_flag'])
			{
				$ret .= ', selected at random';
			}
			if($this->params['exclude_shown_blurbs'])
				$ret .= ' (excluding any that have been shown elsewhere on the same page)';
			$ret .= '</p>';
			return $ret;
		}
	}
?>
