<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

	/**
	 * Include base class & register module with Reason
	 */
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ContentModule';
	
	/**
	 * A minisite module that displays the value of the content field of the current page
	 */
	class ContentModule extends DefaultMinisiteModule
	{
		var $content;
		
		function init( $args = array() )
		{
			$this->content = $this->cur_page->get_value( 'content' );
		}
		function has_content()
		{
			if( carl_empty_html($this->content) )
				return false;
			return true;
		}
		function run()
		{
			$this->process();
			echo '<div id="pageContent" class="'.$this->get_api_class_string().'">';
			echo $this->content;
			echo '</div>';
		}
		function process()
		{
			if (!empty($this->textonly))
				$this->textonly_process();
		}
		function textonly_process()
		{
			if(strstr($this->content, '<img'))
			{
				// Transform images with alt attributes
				$this->content = preg_replace('-<img\s[^>]*alt=(\'|")(.*)\1[^>]*>-isuU', '[$2]', $this->content);
				
				// Transform images without alt attributes, but with title attributes
				$this->content = preg_replace('-<img\s[^>]*title=(\'|")(.*)\1[^>]*>-isuU', '[$2]', $this->content);
				
				// Transform images with neither alt nor title attributes
				$this->content = preg_replace('-<img\s[^>]*>-isuU', '[IMAGE]', $this->content);
			}
		}
		
		function get_documentation()
		{
			return '<p>Displays the current page\'s textual content</p>';
		}
	}
?>
