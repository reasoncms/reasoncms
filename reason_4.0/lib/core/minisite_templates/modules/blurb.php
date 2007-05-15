<?php

	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'BlurbModule';
	
	class BlurbModule extends DefaultMinisiteModule
	{
		var $acceptable_params = array(
		'num_to_display' => '',
		'rand_flag' => false,
		'exclude_shown_blurbs' => true );
		var $es;
		var $blurbs = array();
		
		function init( $args = array() ) // {{{
		{
			parent::init( $args );
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
				if (!empty($already_displayed))
				{
					$this->es->add_relation('entity.id NOT IN ('.join(',',$already_displayed).')');
				}
			}
			if (!empty($this->params['num_to_display'])) $this->es->set_num($this->params['num_to_display']);
			$this->blurbs = $this->es->run_one();
			$this->used_blurbs(array_keys($this->blurbs));
		} // }}}
		
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
				echo '<div class="blurb number'.$i.'">';
				echo tagSearchReplace($blurb->get_value('content'), 'h3', 'h4');
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
