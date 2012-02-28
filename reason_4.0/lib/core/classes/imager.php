<?php
	
	/**
	 *	Class that displays images
	 *
	 *	@package reason
	 *	@subpackage classes
	 */
	 
	 /**
	  * Include dependencies
	  */
	  
	reason_include_once('function_libraries/image_tools.php');

	 
	/**
	 *	Imager Class
	 *	
	 *	The Imager class is a generic class to handle images.  It grabs images 
	 *	from the db and shows them.
	 *
	 *	@author dave hendler
	 */
	class Imager // {{{
	{
		/*
		 *	structure of the images array:
		 *	  images is an array of image arrays.  each key is an ID number, each value is an image array
		 *	image array:
		 *	  the image array is made up of a number of key-value pairs:
		 *		- id
		 *		- image_type
		 *		- description (optional)
		 *		- width (optional)
		 *		- height(optional)
		 */
		var $images;
		var $image_script;
		var $popup_name;
		var $image_dir;
		var $web_image_dir;

		function Imager( $images = '', $image_dir = PHOTOSTOCK, $image_script = REASON_IMAGE_VIEWER, $web_image_dir = WEB_PHOTOSTOCK ) // {{{
		{
			$this->image_script = $image_script;
			$this->image_dir = $image_dir;
			$this->web_image_dir = $web_image_dir;
			$this->popup_name = 'image_popup';
			if ( $images )
				$this->images = $images;
			else $images = array();
		} // }}}
	
		function add_image( $image_id, $description = '', $image_type = 'jpg', $width = '', $height = '', $size = '' ) // {{{
		// adds an image to the internal images structure.  information can be sent
		// either through all 5 parameters or through one array in the first param spot
		{
			$name = '';
			// image info passed as an array
			if ( is_array( $image_id ) )
			{
				// run through all the fields we want to grab, check to see if they are set, then set
				// the local value to either the set value or an empty string.
				// this code preps the local vars for the actual modification to the images array
				$fields = array( 'name','description', 'image_type','width','height','size' );
				while( list( ,$val ) = each( $fields ) )
					$$val = isset( $image_id[ $val ] ) ? $image_id[ $val ] : '';
				$id = $image_id[ 'id' ];
			}
			
			$this->images[ $id ] = array(
				'id' => $id,
				'image_type' => $image_type,
				'width' => $width,
				'height' => $height,
				'size' => $size,
				'description' => $description
			);
			
			$this->fix_image_info( $id );
			
		} // }}}
		function load_images( $association_id, $entity_id ) // {{{
		// needs DB connection to load
		{
			$dbq = new DBSelector;
			$dbq->add_table( 'i' , 'image' );
			$dbq->add_table( 'm' , 'meta' );
			$dbq->add_table( 'r' , 'relationship' );

			$dbq->add_field( 'i' , 'id' );
			$dbq->add_field( 'i' , 'width' );
			$dbq->add_field( 'i' , 'height' );
			$dbq->add_field( 'i' , 'size' );
			$dbq->add_field( 'i' , 'image_type' );
			$dbq->add_field( 'm' , 'description' );

			$dbq->add_relation( 'r.type = ' . $association_id );
			$dbq->add_relation( 'r.entity_a = ' . $entity_id );
			$dbq->add_relation( 'r.entity_b = i.id' );
			$dbq->add_relation( 'i.id = m.id' );

			$res = db_query( $dbq->get_query() , 'Error retrieving images' );
			
			while( $row = mysql_fetch_array( $res, MYSQL_ASSOC ) )
				$this->images[ $row['id'] ] = $row;

		} // }}}
		function load_one_from_reason( $id ) // {{{
		{
			$q = "SELECT * FROM content, image WHERE content.id = '$id' AND image.id = '$id'";
			$r = mysql_query( $q ) OR die( 'Unable to load image: '.mysql_error() );
			$this->add_image( mysql_fetch_row( $r, MYSQL_ASSOC ) );
		} // }}}
	
		function fix_image_info( $id ) // {{{
		{
			$image = $this->images[ $id ];
			
			// get image width and height
			if ( empty( $image['width'] ) OR empty( $image['height'] ) )
			{
				$info = getImageSize( $this->image_dir. reason_get_image_filename($id) );
				$this->images[$id]['width'] = $info[0];
				$this->images[$id]['height'] = $info[1];
			}
			
			if ( empty( $image['size'] ) )
			{
				$this->images[$id]['size'] = round(filesize( $this->image_dir . '/' . $id . '.' . $image['image_type'] ) / 1024);
			}
		} // }}}
	
		function show_thumbnails() // {{{
		{
			if ( !empty( $this->images ) )
			{
				foreach( array_keys( $this->images ) as $id )
				{
					$this->fix_image_info( $id );
					$image = $this->images[$id];
					$window_width = 40 + $image['width'];
					$window_height = 130 + $image['height']; // 96 works on Mac IE 5
					if ($window_width < 326)
						$window_width = 375;
					?>
						<a onmouseover="window.status = 'view larger image'; return true;" onmouseout="window.status = ''; return true;" 							href="javascript:void(window.open('<?php echo $this->image_script ?>?id=<?php echo $image['id']; ?>',							'<?php echo $this->popup_name; ?>','menubar,scrollbars,resizeable,width=<?php echo $window_width;?>,							height=<?php echo $window_height; ?>'))"><img src="<?php echo $this->web_image_dir.'/'.reason_get_image_filename($id, 'tn'); ?>" 
							alt="<?php echo strip_tags(str_replace('"','',$image['description'])); ?>" border="0" /><br /><?php
					echo '<span class="smallText">';
					$this->show_description( $id );
					echo '</span></a><br /><br />';
				}
			}
		} // }}}
		function get_image( $id ) // {{{
		{
			$this->fix_image_info( $id );
			$image = $this->images[ $id ];
			
			$img = '<img src="'.$this->web_image_dir.'/'. reason_get_image_filename($id) .'" ';
			if ( $image['width'] AND $image['height'] )
				$img .= 'width="'.$image[ 'width' ].'" height="'.$image[ 'height' ].'" ';
			$img .= 'alt="'.strip_tags(str_replace( '"',"'",$image[ 'description' ])).'" border="0" />';
			return $img;
		} // }}}
		function show_image( $id ) // {{{
		{
			echo $this->get_image( $id );
		} // }}}
		function show_description( $id ) // {{{
		{
			if ( $this->images[$id]['description'] )
				echo $this->images[$id]['description'];
		} // }}}
	} // }}}
?>
