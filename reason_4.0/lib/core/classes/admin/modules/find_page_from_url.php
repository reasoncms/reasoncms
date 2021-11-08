<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module and other needed utilities
  */
reason_include_once('classes/admin/modules/default.php');
include_once( DISCO_INC . 'disco.php');
reason_include_once( 'function_libraries/URL_History.php' );

/**
 * An administrative module that allows a use to select a site to borrow a given item into
 */
class FindPageFromURLModule extends DefaultModule
{
	
	function FindPageFromURLModule( &$page )
	{
		$this->admin_page =& $page;
	}
	/**
	 * Initialize the module
	 * @return void
	 */
	function init()
	{
		$this->admin_page->title = 'Find Page From URL';
	}
	/**
	 * Run the module & produce output
	 * @return void
	 */
	function run()
	{
		if( !reason_user_has_privs( $this->admin_page->user_id, 'view_sensitive_data' ) )
		{
			echo 'You do not have the "view_sensitive_data" privilege necessary to use this tool.';
			return;
		}
		
		$d = new Disco();
		$d->set_box_class('StackedBox');
		$d->add_element('url');
		$d->set_actions(array('find'=>'Find'));
		$d->run();
		
		if($url = $d->get_value('url'))
		{
			$history = array();
			
			if($path = parse_url($url, PHP_URL_PATH))
			{
				$history = reason_get_url_history( $path );
			}
			else
			{
				echo 'Unable to parse URL path. Please try using a fully qualified URL, like http://domain.name/etc/.<br />';
				break;
			}
			
			if(!empty($history))
			{
				echo '<ul>';
				foreach($history as $row)
				{
					echo '<li>';
					echo $this->get_history_item_report($row);
					echo '</li>';
				}
				echo '</ul>';
			}
			else
			{
				echo 'No history items found for this URL.<br />';
			}
		}
	}
	
	function get_history_item_report($row)
	{
		$ret = '';
		$ret .= '<strong>Page id: '.$row['page_id'].'</strong><br />';
		$ret .= 'Last confirmed date &amp; time at this URL: '.date('Y-m-d H:i:s', $row['timestamp']).'<br />';
		$page = new entity($row['page_id']);
		if (reason_is_entity($page, 'minisite_page'))
		{
			$ret .= 'Is still in the Reason database<br />';
			$ret .= 'State: '.$page->get_value('state').'<br />';
			if('Live' == $page->get_value('state'))
			{
				if($redir = @reason_get_page_url($page))
				{
					$ret .= 'Current URL: <a href="'.reason_htmlspecialchars($redir).'">'.reason_htmlspecialchars($redir).'</a><br />';
				}
			}
			$ret .= '<a href="?entity_id_test='.$row['page_id'].'&cur_module=EntityInfo">Entity Info</a><br />';
		}
		else
		{
			$ret .= 'Is not in Reason db any longer<br />';
		}
		return $ret;
	}
}