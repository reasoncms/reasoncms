<?php
/**
 * @package reason
 * @subpackage content_previewers
 */
	/**
	 * Include dependencies & register previewer with Reason
	 */

	$GLOBALS[ '_content_previewer_class_names' ][ basename( __FILE__) ] = 'av_previewer';
		
	/**
	 * A content previewer for media works
	 *
	 */
	class av_previewer extends default_previewer
	{
		function init( $id , &$page )
		{
			parent::init($id , &$page);
			
			if ($this->_entity->get_value('integration_library') == 'kaltura')
			{
				$this->admin_page->head_items->add_javascript(JQUERY_URL, true);		$this->admin_page->head_items->add_javascript(WEB_JAVASCRIPT_PATH.'content_previewers/media_work.js');
			}
		}
	
		function display_entity()
		{
			$this->start_table();
			
			// Kaltura-integrated-specific info
			if ($this->_entity->get_value('integration_library') == 'kaltura')
			{
				$this->add_file_preview();
				$this->add_embed_code();
				
				if ($this->_entity->get_value('transcoding_status') == 'converting')
				{
					$this->show_item_default( 'status_report' , 'Your media file is currently being processed.<br /><img src="'.REASON_HTTP_BASE_PATH.'modules/av/in_progress_bar.gif" width="220" height="19" alt="" />');	
				}	
				elseif ($this->_entity->get_value('transcoding_status') == 'error')
				{
					$this->show_item_default( 'status_report', "There was an error when your media was processed.");
				}
			}
			
			// Everything Else
			$this->show_all_values( $this->_entity->get_values() );
			
			$this->end_table();
		}
		
		function add_file_preview()
		{
			if($this->_entity->get_value('transcoding_status') == 'ready')
			{
				reason_include_once( 'classes/media_work_displayer.php' );
				$displayer = new MediaWorkDisplayer();
	
				$entity = new entity($this->_entity->get_value('id'));
				$displayer->set_media_work($entity);
				$displayer->set_height('small');
	
				$embed_markup = $displayer->get_iframe_markup();

				if(!empty($embed_markup))
				{
					$this->show_item_default( 'preview', $embed_markup);
				}
			}
		}	
		
		function add_embed_code()
		{
			$entity = new entity($this->_entity->get_value('id'));
			if($entity->get_value('transcoding_status') == 'ready')
			{
				$displayer = new MediaWorkDisplayer();
				
				$displayer->set_media_work($entity);
				
				if ($entity->get_value('av_type') == 'Video')
				{
					$displayer->set_height('small');
					$embed_markup_small = $displayer->get_iframe_markup();
					
					$displayer->set_height('medium');
					$embed_markup_medium = $displayer->get_iframe_markup();
					
					$displayer->set_height('large');
					$embed_markup_large = $displayer->get_iframe_markup();
		
					if (!empty($embed_markup_small))
					{
						$this->show_embed_item('Small Embedding Code', $embed_markup_small);
						$this->show_embed_item('Medium Embedding Code', $embed_markup_medium);
						$this->show_embed_item('Large Embedding Code', $embed_markup_large);
					}
				}
				else
				{
					$displayer->set_height('small');
					$embed_markup_small = $displayer->get_iframe_markup();
					if (!empty($embed_markup_small))
					{
						$this->show_embed_item('Audio Embedding Code', $embed_markup_small);
					}
				}
			}
		}
		
		function show_embed_item( $field , $value ) // {{{
        {
            echo '<tr id="'.str_replace(' ', '_', $field).'_Row">';
            $this->_row = $this->_row%2;
            $this->_row++;
 
            echo '<td class="listRow' . $this->_row . ' col1">';
            if($lock_str = $this->_get_lock_indication_string($field))
                echo $lock_str . '&nbsp;';
            echo prettify_string( $field );
            if( $field != '&nbsp;' ) echo ':';
            echo '</td>';
            echo '<td class="listRow' . $this->_row . ' col2"><input id="'.$field.'Element" type="text" readonly="readonly" size="50" value="'.htmlspecialchars($value).'"></td>';
 
            echo '</tr>';
        } // }}}
		
	}
?>
