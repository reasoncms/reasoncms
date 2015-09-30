<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 	/**
 	 * Include the base class & register module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'basicTabsModule';

	/**
	 * Minisite module that builds a tabset among a page and its children
	 */
	class basicTabsModule extends DefaultMinisiteModule
	{
		var $acceptable_params = array(
			'mode' => 'child', // alternate mode: 'parent'
			'css' => '',
		);
		var $_parent_page;
		var $_siblings;

		function init( $args = array() )	 // {{{
		{
			parent::init( $args );
			
			if($this->params['mode'] == 'parent')
			{
				$children_ids = $this->parent->pages->children( $this->page_id );
				reset($children_ids);
				$first_child = current($children_ids);
				$destination = $this->parent->pages->get_full_url( $first_child, true );
				header('Location: '.$destination);
				die();
			}
			
			$parent_page_id = $this->parent->pages->parent($this->page_id);
			$this->_parent_page = new entity($parent_page_id);
			
			$children_ids = $this->parent->pages->children( $parent_page_id );
			foreach( $children_ids as $sibling_id )
			{
				$this->_siblings[$sibling_id] = new entity($sibling_id);
				
				$tab_title = $this->_siblings[$sibling_id]->get_value('link_name') ? $this->_siblings[$sibling_id]->get_value('link_name') : $this->_siblings[$sibling_id]->get_value('name');
				$this->_siblings[$sibling_id]->set_value('tab_title',$tab_title);
				
				$tab_link = $this->_siblings[$sibling_id]->get_value('url') ? $this->_siblings[$sibling_id]->get_value('url') : $this->parent->pages->get_full_url( $sibling_id );
				$this->_siblings[$sibling_id]->set_value('tab_link',$tab_link);
			}
			if(!empty($this->params['css']))
			{
				$this->parent->add_stylesheet($this->params['css']);
			}
			else
			{
				$this->parent->add_stylesheet(REASON_HTTP_BASE_PATH.'css/basic_sibling_tabs.css');
			}
		} // }}}
		
		function has_content() // {{{
		{
			return true;
		} // }}}
		
		function run() // {{{
		{
			echo '<h2 class="pageTitle"><span>'.$this->_parent_page->get_value('name').'</span></h2>'."\n";
			echo '<div class="basicTabs">'."\n";
			echo '<ul>';
			$num_siblings = count($this->_siblings);
			$i = 1;
			foreach($this->_siblings as $sibling)
			{
				$tab = $sibling->get_value('tab_title');
				
				$classes = array();
				if($i == 1) $classes[] = 'first';
				if($i == $num_siblings) $classes[] = 'last';
				
				if($sibling->id() == $this->page_id)
				{
					$tab = '<strong>'.$tab.'</strong>';
					$classes[] = 'current';
				}
				if($sibling->id() != $this->page_id || $this->parent->pages->should_link_to_current_page())
				{
					$tab = '<a href="'.$sibling->get_value('tab_link').'">'.$tab.'</a>';
				}
				if(!empty($classes))
					echo '<li class="'.implode(' ',$classes).'">'.$tab.'</li>';
				else
					echo '<li>'.$tab.'</li>';
				$i++;
			}
			echo '</ul>'."\n";
			echo '</div>'."\n";
		} // }}}
	}
?>
