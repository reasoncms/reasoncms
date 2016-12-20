<?php
/**
 * @package reason
 * @subpackage classes
 */
 
/**
 * Include the base class
 */
include_once( 'reason_header.php' );
reason_include_once( 'classes/mvc.php' );

/**
 * Register MVC component with Reason
 */
$GLOBALS[ '_reason_mvc_view_class_names' ][ reason_basename(__FILE__) ] = 'YouTubeLatestUserVideosDefaultFeedView';

/**
 * YouTubeLatestUserVideosDefaultFeedView displays data from the latest_user_videos model.
 *
 * These configuration params are supported:
 *
 * - num_to_show (int default 4)
 * - title (string default NULL)
 * - description (string default NULL)
 * - randomize (boolean default false)
 *
 */
 
class YouTubeLatestUserVideosDefaultFeedView extends ReasonMVCView
{
	var $config = array('num_to_show' => 4,
						'title' => NULL,
						'description' => NULL,
                        'randomize' => false,
        );

	function get()
	{
		$latest_uploads = $this->data();
		$str = '';
		if (!empty($latest_uploads) && empty($latest_uploads['error']))
		{
			if ($this->config('randomize')) shuffle($latest_uploads);
			$str .= '
                <style media="screen">
                    .youtube-list ul {
                    list-style: square outside none;
                    }
                    
                    .youtube-list > li img {
                    float: left;
                    margin: 0 15px 0 0;
                    }
                    
                    
                    .youtube-list > li {
                    padding: 10px;
                    overflow: auto;
                    }
                    
                    .youtube-list > li:hover {
                    background: #ccc;
                    cursor: pointer;
                    }
                </style>';
            if (!is_null($this->config('title')))
            {
                $str .= '<h3>'.$this->config('title').'</h3>';
            }
            if (!is_null($this->config('description')))
            {
                $str .= '<p>'.$this->config('description').'</p>';
            }

            $str .= '<ul class="youtube-list">';

            foreach ($latest_uploads as $upload)
			{
				$num = (!isset($num)) ? 1 : ($num + 1);
				$str .= '
                <li>
                    <a href="'.$upload['url'].'">
                        <div class="tnImage">
                            <img src="'.$upload['thumbnail'].'">
                        </div>
                        <span>'.$upload['title'].'</span>
                    </a>
                </li>';
				if ($num == $this->config('num_to_show')) break;
			}
			$str .= '</ul>';
		}
		else if (!empty($latest_uploads['error']))
        {
            $str = $latest_uploads['error'];
        }
		else $str = 'data was empty';
		return $str;
	}
}
?>