<?php
/**
 * @package reason
 * @subpackage content_previewers
 */
	/**
	 * Register previewer with Reason
	 */
	$GLOBALS[ '_content_previewer_class_names' ][ basename( __FILE__) ] = 'page_previewer';

	/**
	 * A content previewer for minisite pages
	 */
	class page_previewer extends default_previewer
	{
		function run()
		{
			if (isset($_REQUEST['kill_cache']))
			{
				$this->clear_page_cache();
				$redirect = carl_make_redirect(array('kill_cache' => ''));
				header("Location: " . $redirect);
				exit();
			}
			parent::run();
		}
		
		function display_entity() // {{{
		{
			$this->start_table();
			
			// iFrame Preview
			if( !$this->_entity->get_value( 'url' ) && $this->_entity->get_value( 'state' ) == 'Live' )
			{
				// iFrame Preview
				reason_include_once( 'function_libraries/URL_History.php' );
				$url = reason_get_page_url( $this->_entity->id() );
				if ($url)
				{
					//$this->show_item_default( 'Public View of Page' , '<iframe src="'.$url.'" width="100%" height="400"></iframe>' );

					// iframe replacement method
					// http://intranation.com/test-cases/object-vs-iframe/
					//  classid="clsid:25336920-03F9-11CF-8FD0-00AA00686F13"
					$this->show_item_default( 'Public View of Page' , '<object type="text/html" data="'.$url.'" class="pageViewer"></object><p><a href="'.$url.'" target="_new">Open page in new window</a></p>');
					//$this->show_item_default( 'Public View of Page' , '<iframe src="'.$url.'" class="pageViewer"></iframe><p><a href="'.$url.'" target="_new">Open page in new window</a></p>');
				}
			}
			
			// 
			$this->show_item_default('Cache Status', $this->get_cache_status());
			
			// Everything Else
			$this->show_all_values( $this->_entity->get_values() );
			
			$this->end_table();
		} // }}}
		
		function show_item_extra_head_content( $field , $value )
		{
			$this->show_item_default( $field, nl2br(htmlspecialchars($value)));
		}
		
		function clear_page_cache()
		{
			$rpc = $this->get_reason_page_cache();
			if ($rpc->page_cache_exists())
			{
				return $rpc->delete_page_cache();
			}
		}
		
		function get_cache_status()
		{
			$rpc = $this->get_reason_page_cache();
			if ($rpc->page_cache_exists() && !$rpc->page_cache_is_empty())
			{
				$link = carl_make_link(array('kill_cache' => 1));
				return 'Cached. (<a href="'.$link.'">delete cache</a>)';
			}
			else return 'Not cached.';
		}
		
		function get_reason_page_cache()
		{
			if (!isset($this->_reason_page_cache))
			{
				reason_include_once('classes/page_cache.php');
				$this->_reason_page_cache = new ReasonPageCache();
				$this->_reason_page_cache->set_site_id($this->admin_page->site_id);
				$this->_reason_page_cache->set_page_id($this->admin_page->id);
			}
			return $this->_reason_page_cache;
		}
	}
?>
