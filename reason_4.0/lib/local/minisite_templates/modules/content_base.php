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
			echo '<div id="pageContent">';
			$this->luther_process_images();
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
		function luther_replace_image($match)
		{
		// identify is an imagemagick command
		// use it to extract the width of the thumbnail
		$width = exec("identify -verbose " . $_SERVER['DOCUMENT_ROOT'] . "reason/images/" . $match[3] . $match[4] . $match[5] . " | grep Geometry:", $output, $returnvalue);
		if ($returnvalue != 0)
		{
			$width = 125;
		}
		else
		{
			$width = preg_replace('/Geometry:\s+(\d+)x\d+/', '$1', $width);
		}
		$text = '<div class="figure" style="width: '. $width . 'px"><a href="' . $match[2] . $match[3] . $match[5] . '" class="highslide" onclick="return hs.expand(this)"><img src="' . $match[2] . $match[3] . $match[4] . $match[5] . '" border="0" alt="' . $match[1] .'" title="click to enlarge"/></a>' . $match[1] .'</div>';
		return $text;

		}
		function luther_process_images()
		// use figure style for images and add highslide functionality
		{
			//$this->content = preg_replace_callback('-<img\s*alt="(.*)"\s*src="(.*)(\d+)(_tn)(\.jpe?g|JPE?G|Jpe?g|gif|GIF|Gif|png|PNG|Png)"(\s?align="\w+")?\s?\/>-isuU', array($this, 'luther_replace_image'), $this->content);
			//$this->content = preg_replace('-<img\s*alt="(.*)"\s*src="(.*)(_tn)(.*)"\s?\/>-isuU', '<div class="figure" style="width: 125px"><a href="'.'$2'.'$4'.'" class="highslide" onclick="return hs.expand(this)"><img src="'.'$2'.'$3'.'$4'.'" border="0" alt="'.'$1'.'" title="click to enlarge"/></a>'.'$1'.'</div>', $this->content);
			$this->content = preg_replace('-<img(.*)(align="(\w+))"-', '<img'.'$1'.'class="'.'$3'.'" ', $this->content);
			

		}
	}
?>
