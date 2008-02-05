<?php
	reason_include_once( 'minisite_templates/modules/image_sidebar.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ImageSlideshowModule';
	
	class ImageSlideshowModule extends ImageSidebarModule
	{
		function handle_params( $params )
		{
			$this->acceptable_params['slideshow_type'] = 'auto';
			parent::handle_params($params);
		}
		function init( $args = array() )
		{
			parent::init( $args );
			$this->parent->add_head_item('script',array('src'=>REASON_HTTP_BASE_PATH.'js/SmoothSlideshow/scripts/prototype.lite.js','type'=>'text/javascript' ) );
			$this->parent->add_head_item('script',array('src'=>REASON_HTTP_BASE_PATH.'js/SmoothSlideshow/scripts/moo.fx.js','type'=>'text/javascript' ) );
			$this->parent->add_head_item('script',array('src'=>REASON_HTTP_BASE_PATH.'js/SmoothSlideshow/scripts/moo.fx.pack.js','type'=>'text/javascript' ) );
			if($this->params['slideshow_type'] == 'auto')
			{
				$this->parent->add_head_item('script',array('src'=>REASON_HTTP_BASE_PATH.'js/SmoothSlideshow/scripts/timed.slideshow.js','type'=>'text/javascript' ) );
			}
			elseif($this->params['slideshow_type'] == 'manual')
			{
				$this->parent->add_head_item('script',array('src'=>REASON_HTTP_BASE_PATH.'js/SmoothSlideshow/scripts/showcase.slideshow.js','type'=>'text/javascript' ) );
			}
			else
			{
				trigger_error($this->params['slideshow_type'].' is not a valid slideshow type. valid slideshow types are "auto" and "manual"');
			}
			$this->parent->add_stylesheet(REASON_HTTP_BASE_PATH.'js/SmoothSlideshow/css/jd.slideshow.css');
		}
		function run() // {{{
		{
			if(!empty($this->textonly))
			{
				$this->run_text_only();
			}
			else
			{
				$this->run_full_graphics();
			}
		}
		function run_text_only()
		{
			echo '<ul>';
			foreach( $this->images AS $id => $image )
			{
				echo '<li>';
				echo '<a href="'.WEB_PHOTOSTOCK.$id.'.'.$image->get_value('image_type').'">Image: '.$image->get_value('description').'</a>';
				echo '</li>'."\n";
			}
			echo '</ul>'."\n";
		}
		function run_full_graphics()
		{
			$die = isset( $this->die_without_thumbmail ) ? $this->die_without_thumbnail : false;
			$popup = isset( $this->show_popup_link ) ? $this->show_popup_link : true;
			$desc = isset( $this->description ) ? $this->description : true;
			$text = isset( $this->additional_text ) ? $this->additional_text : "";
			
			$max_dimensions = $this->get_max_dimensions();
			
			echo '<div class="imageSlideshow">'."\n";
			echo '<div class="timedSlideshow" id="mySlideshow" style="height:'.$max_dimensions['height'].'px;width:'.$max_dimensions['width'].'px;"></div>'."\n";
			echo '<script type="text/javascript">'."\n";
			echo 'countArticle = 0;'."\n";
			echo 'var mySlideData = new Array();'."\n";
			foreach( $this->images AS $id => $image )
			{
				$show_text = $text;
				if( !empty( $this->show_size ) )
					$show_text .= '<br />('.$image->get_value( 'size' ).' kb)';
				echo 'mySlideData[countArticle++] = new Array('."\n";
				echo "'".WEB_PHOTOSTOCK.$id.'.'.$image->get_value('image_type')."',\n";
				echo "'#',\n";
				echo "'".htmlspecialchars( strip_tags($image->get_value('description')),ENT_QUOTES,'UTF-8' )."',\n";
				echo "'".htmlspecialchars( strip_tags($image->get_value('content')),ENT_QUOTES,'UTF-8' )."'\n";
				echo ');'."\n";
			}
			?>function addLoadEvent(func) {
var oldonload = window.onload;
if (typeof window.onload != 'function') {
window.onload = func;
} else {
window.onload = function() {
oldonload();
func();
} } }

function startSlideshow() {
initSlideShow($Prototype('mySlideshow'), mySlideData);
}

addLoadEvent(startSlideshow);
<?php
			echo '</script>'."\n";
			echo '<noscript>'."\n";
			echo '<ul>';
			foreach( $this->images AS $id => $image )
			{
				echo '<li><img src="'.WEB_PHOTOSTOCK.$id.'.'.$image->get_value('image_type').'" alt="'.htmlspecialchars( strip_tags($image->get_value('description')),ENT_QUOTES,'UTF-8' ).'" /><div>'.$image->get_value('description').'</div></li>';
			}
			echo '</ul>';
			echo '</noscript>'."\n";
			echo '</div>'."\n";
		} // }}}
		
		function get_max_dimensions()
		{
			$width = 0;
			$height = 0;
			foreach( $this->images AS $image )
			{
				if($image->get_value('height') > $height)
					$height = $image->get_value('height');
				if($image->get_value('width') > $width)
					$width = $image->get_value('width');
			}
			if($width == 0)
				$width = 500;
			if($height == 0)
				$height = 500;
			return array('height'=>$height,'width'=>$width);
		}
	}
?>