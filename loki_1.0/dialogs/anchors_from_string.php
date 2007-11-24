<?php
/**
 * @package loki_1
 */

 /**
 * Get the simple named anchors from an html string (only finds anchors 
 * in case-insensitive form <a name="asd"*</a>
 */
 function find_anchors_naive($string)
 {
 	$string=strip_tags($string, "<a>");
 	$anchor_names = array();
 	while($string = stristr($string,'<a name='))
 	{
 		$string = substr ( $string, 8 );
 		$quote = substr ( $string, 0,1 );
 		$string = substr ( $string, 1 );
 		//echo $quote.'<br />';
 		//echo $string.'<br />';
 		if($quote == '"' || $quote == "'")
 		{
 			$pos = strpos( $string, $quote );
 			if($pos)
 			{
 				//echo $pos.'<br />';
 				$name = substr ( $string, 0, $pos );
 				//echo $name.'<br />';
 				$anchor_names[] = strip_tags(str_replace(array('"',"'"),'',$name));
 			}
 		}
 		$string = substr ( stristr($string,'</a>'),4);
 	}
 	return $anchor_names;
 }
 ?>