<?php
	/**
	 * Utility functions for image display
	 *
	 * @author dave hendler
	 * @package reason
	 * @subpackage function_libraries
	 */

if( !defined( 'INC_REASON_MODULES_IMAGES' ) )
{
	define( 'INC_REASON_MODULES_IMAGES', true );
	
	reason_include_once( 'classes/imager.php' );

	/**
	 * Get the mearkup to show an image thumbnail
	 *
	 * This function is a wrapper for show_image() that returns what show_image() would
	 * normally simply output.
	 *
	 * @param mixed $image An image object, image ID, or array of image values
	 * @param boolean $die_without_thumbnail Echo nothing if no thumb available? (default behavior 
	 *                                       is to make link to full-sized image if no thumb available)
	 * @param boolean $show_popup_link Wrap image in link that pops up image popup?
	 * @param boolean $show_description Place the image description (i.e. short caption) below the image?
	 * @param string $other_text Text to use instead of image description
	 * @param boolean $textonly True sets function into textonly mode, which instead of outputting
	 *                          image markup outputs a text description linking to image
	 * @param boolean $show_author Output the value of the author field below the description?
	 * @param string $link_with_url Wrap the image in a link to this URL instead of to image popup
	 * @return string XHTML markup
	 */
	function get_show_image_html( $image, $die_without_thumbnail = false, $show_popup_link = true, $show_description = true, $other_text = '' , $textonly = false, $show_author = false, $link_with_url = '' ) // {{{
	{
		ob_start();
		show_image( $image, $die_without_thumbnail, $show_popup_link, $show_description, $other_text, $textonly, $show_author, $link_with_url);
		$result = ob_get_contents();
		ob_end_clean();
		return $result;
	}
	
	/**
	 * Show an image thumbnail
	 *
	 * Note that this function does not return XHTML; instead it directly outputs it
	 * via echo statements. If you want returned XHTML, use get_show_image_html().
	 *
	 * @param mixed $image An image object, image ID, or array of image values
	 * @param boolean $die_without_thumbnail Echo nothing if no thumb available? (default behavior 
	 *                                       is to make link to full-sized image if no thumb available)
	 * @param boolean $show_popup_link Wrap image in link that pops up image popup?
	 * @param boolean $show_description Place the image description (i.e. short caption) below the image?
	 * @param string $other_text Text to use instead of image description
	 * @param boolean $textonly True sets function into textonly mode, which instead of outputting
	 *                          image markup outputs a text description linking to image
	 * @param boolean $show_author Output the value of the author field below the description?
	 * @param string $link_with_url Wrap the image in a link to this URL instead of to image popup
	 * @return void
	 *
	 * @todo Make a better image markup library!
	 */
	function show_image( $image, $die_without_thumbnail = false, $show_popup_link = true, $show_description = true, $other_text = '' , $textonly = false, $show_author = false, $link_with_url = '' ) // {{{
	{
		if( is_array( $image ) )
		{
			$id = $image['id'];
		}
		else if ( is_object( $image ) )
		{
			$values = $image->get_values();
			$id = $image->id();
			$image = $values;
		}
		else
		{
			$id = $image;
			$image = get_entity_by_id( $id );
		}

		$tn_name = PHOTOSTOCK.$id.'_tn'.'.'.$image['image_type'];
		$full_image_name = PHOTOSTOCK.$id.'.'.$image['image_type'];
		
		if( file_exists( $tn_name ) )
		{
			$tn = true;
			$image_name = $id.'_tn.'.$image['image_type'];
		}
		else
		{
			if( $die_without_thumbnail )
				return;
			$tn = false;
			$image_name = $id.'.'.$image['image_type'];
		}
		if( file_exists( PHOTOSTOCK.$image_name ) )
		{
			list($width,$height) = getimagesize( PHOTOSTOCK.$image_name );

			$full_image_exists = file_exists( $full_image_name );

			if( !$image['description'] )
				if( $image['keywords'] )
					$image['description'] = $image['keywords'];
				else
					$image['description'] = $image['name'];

			$mod_time = filemtime( PHOTOSTOCK.$image_name );

			$window_width = $image['width'] < 340 ? 340 : 40 + $image['width'];
			$window_height = 170 + $image['height']; // formerly 130 // 96 works on Mac IE 5
			if (empty($link_with_url))
			{
				if (empty($textonly))
					$pre_link = "<a onmouseover=\"window.status = 'view larger image'; return true;\" onmouseout=\"window.status = ''; return true;\" onclick=\"this.href='javascript:void(window.open(\'".REASON_IMAGE_VIEWER."?id=".$image['id']."\', \'PopupImage\', \'menubar,scrollbars,resizable,width=".$window_width.",height=".$window_height."\'))'\" href=\"".WEB_PHOTOSTOCK.$id.'.'.$image['image_type']."?cb=".filemtime(PHOTOSTOCK.$image_name)."\">";
					else
						$pre_link = '<a href="'.WEB_PHOTOSTOCK.$id.'.'.$image['image_type'].'?cb='.filemtime(PHOTOSTOCK.$image_name).'">';
			}
			else
			{
				$pre_link = '<a href="'.$link_with_url.'">';
			}
			
			if (empty($textonly))
			{
				echo '<div class="tnImage">';
				if( ($tn AND $show_popup_link AND $full_image_exists) || ($tn && !empty($link_with_url) ) )
					echo $pre_link;
						
				// show photo
				echo '<img src="'.WEB_PHOTOSTOCK.$image_name.'?cb='.$mod_time.'" width="'.$width.'" height="'.$height.'" alt="'.reason_htmlspecialchars( $image['description'] ).'" class="thumbnail" border="0"/>';
				if( ($tn AND $show_popup_link AND  $full_image_exists) || ($tn && !empty($link_with_url) ) )
				{
					echo '</a>';
				}
	
				echo '</div>';
			}

			if( $show_description )
			{
				$desc =& $image['description'];
				if (empty($textonly))
					echo '<div class="tnDesc smallText">';
				else
					echo'<div class="tnDesc">';
				if( $tn AND $show_popup_link AND $full_image_exists )
				{
					echo $pre_link.$desc.'</a>';
				}
				else
				{
					echo $desc;
				}
				echo '</div>';
			}
			if( $other_text )
			{
				if (empty($textonly))
					echo '<div class="tnDesc smallText">';
				else
					echo'<div class="tnDesc">';
				if( $tn AND $show_popup_link AND $full_image_exists )
					echo $pre_link.$other_text.'</a>';
				else
					echo $other_text;
				echo '</div>';
			}
			if( $show_author AND !empty( $image[ 'author' ] ) )
			{
				echo '<div class="tnAuthor smallText">Photo: '.$image[ 'author' ].'</div>';
			}
		}
	} // }}}

	function init_imager( ) // {{{
	{
		return new Imager( '', PHOTOSTOCK,REASON_IMAGE_VIEWER);
	} // }}}	
	function load_images( $association_id, $entity_id )  // {{{
	{
		$i = init_imager();
		$i->load_images( $association_id , $entity_id );
		/*$q = "SELECT i.* ".
			 "FROM image AS i, ".
			 "	   site AS s, ".
			 "     content AS c, ".
			 "     site_to_image AS sti, ".
			 "     content_to_image AS cti ".
			 "WHERE s.id = '".$site_id."' ".
			 "  AND c.id = '".$content_id."' ".
			 "  AND s.id = sti.site_id ".
			 "  AND i.id = sti.image_id ".
			 "  AND c.id = cti.content_id ".
			 "  AND i.id = cti.image_id ";
		$r = mysql_query( $q ) OR die( 'Unable to retrieve images: '.mysql_error() );
		while( $row = mysql_fetch_row( $r, MYSQL_ASSOC ) )
		{
			$i->add_image( $row );
		}*/
		return $i;
	} // }}} 
	/**
	 * Get the contents of the image table for a given ID
	 * @deprecated
	 * @param integer $id
	 * @return array
	 * @todo Remove from Reason
	 */
	function get_image_info( $id ) //  {{{
	{
		trigger_error('get_image_info() is deprecated. Use the standard Reason API (entity class) instead.');
		$q = "SELECT * FROM  image WHERE id = '$id'";
		$r = mysql_query( $q ) OR die( 'Unable to load image: '.mysql_error() );
		return mysql_fetch_array( $r, MYSQL_ASSOC );
	} // }}}
}
?>
