<?php

class eventsGalleryBig_listTemplate
{
	function add_head_items($head_items)
	{
		$head_items->add_stylesheet(REASON_HTTP_BASE_PATH . 'modules/events_gallery/big_list.css');
	}
	function get_markup($events)
	{
		$ret = '<div id="eventsGalleryModule" class="bigList">'."\n";
		$ret .= '<ul class="events">'."\n";
		foreach($events as $event)
		{
			$ret .= '<li class="event">'."\n";
			$ret .= '<a href="'.reason_htmlspecialchars($event->get_url()).'">'."\n";
			$ret .= '<span class="imageWrap">'."\n";
			if($image = $event->get_image())
			{
				reason_include_once('classes/sized_image.php');
				$rsi = new reasonSizedImage();
				$rsi->set_id($image->id());
				$rsi->set_width(280);
				$rsi->set_height(200);
				$url = $rsi->get_url();
				$ret .= '<img src="'.htmlspecialchars($url).'" alt="'.reason_htmlspecialchars(strip_tags($image->get_value('description'))).'" width="295" height="200" class="primaryImage" />'."\n";
			}
			$ret .= '</span>'."\n";
			$ret .= '<span class="info">'."\n";
			$ret .= '<span class="meta">'."\n";
			$ret .= '<em class="currency">'.htmlspecialchars($event->temporal_phrase()).'</em> / ';
			$ret .= '<span class="dates">'.htmlspecialchars($event->date_range_phrase()).'</span>'."\n";
			$ret .= '</span><br />'."\n";
			$ret .= '<span class="name">'."\n";
			$ret .= '<strong class="title">';
			$ret .= $event->get_main_title($event);
			if($sub = $event->get_subtitle($event))
				$ret .= ':';
			$ret .= '</strong>';
			if(!empty($sub))
			{
				$ret .= '<span class="subtitle">'.$sub.'</span>'."\n";
			}
			$ret .= '</span>'."\n";
			$ret .= '</span>'."\n";
			$ret .= '</a>'."\n";
			$ret .= '</li>'."\n";
		}
		$ret .= '</ul>'."\n";
		$ret .= '</div>'."\n";
		return $ret;
	}
}

?>
