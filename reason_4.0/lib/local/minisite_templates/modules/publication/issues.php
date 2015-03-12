<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
/**
 * Include parent class and register module with Reason
*/
reason_include_once( 'minisite_templates/modules/publication/module.php' );
	
$GLOBALS[ '_module_class_names' ][ 'publication/'.basename( __FILE__, '.php' ) ] = 'publicationIssuesModule';
	
/**
 * A minisite module that outputs the description of the publication attached to the
 * current page
*/
class publicationIssuesModule extends PublicationModule
{
	var $publication;
	var $issues;
	var $links_to_issues;
	var $current_issue;
	
	var $acceptable_params = array(
		'show_datetime' => false,
	);

	function init( $args = array() )
	{
		$es = new entity_selector( $this->site_id );
		$es->description = 'Selecting publications for this page';
		$es->add_type( id_of('publication_type') );
		$es->add_right_relationship( $this->page_id, relationship_id_of('page_to_publication') );
		$es->set_num( 1 );
		$publications = $es->run_one();
		if(!empty($publications))
		{
			$this->publication = current($publications);
			if($this->publication->get_value('has_issues') == 'yes')
			{
				$this->init_issue();
				$this->issues =& $this->get_visible_issues();
				$this->links_to_issues = $this->get_links_to_issues();
				$this->current_issue = $this->get_current_issue();
			}
		}

	}
	function has_content()
	{
		if( !empty($this->publication) && $this->has_issues())
			return true;
		else
			return false;
	}
	function run()
	{
		echo '<div id="blogDescription">'."\n";
		echo $this->get_issue_links_markup();
		echo '</div>'."\n";
	}
		
	function get_issue_links_markup()
	{
		$markup_string = '';
		$cur_issue_id = '';
		if(!empty($this->current_issue))
		{
			$cur_issue_id = $this->current_issue->id();
		}
		
		if(count($this->issues) > 1 )
		{
			$markup_string .= '<div class="issueMenu">'."\n";
			$markup_string .= '<form action="'.htmlspecialchars(get_current_url(),ENT_QUOTES,'UTF-8').'">'."\n";
			$markup_string .= '<label for="pubIssueMenuElement" class="issueLabel">Issue:</label>'."\n";

			$markup_string .= '<script type="text/javascript">'."\n";
			$markup_string .= '/* <![CDATA[ */'."\n";
			$markup_string .= '
			if (jQuery)
			{
				$(document).ready(function(){
					$(".issueMenu input[type=\'submit\']").hide();
					$(".issueMenu select[name=\'issue_id\']").change(function(){
						$(this).parent("form").submit();
					});
				});
			}';
			$markup_string .= '/* ]]> */'."\n";
			$markup_string .= '</script>';
			
			$markup_string .= '<select name="issue_id" id="pubIssueMenuElement">'."\n";
			if (!$cur_issue_id)
			{
				$markup_string .= '<option value="'.$cur_issue_id.'" selected="selected">Select Issue</option>'."\n";
			}
			foreach($this->issues as $id => $issue)
			{
				$selected = ($cur_issue_id == $id) ? ' selected="selected"' : '';
				$markup_string .= '<option value="'.$id.'"'.$selected.'>'.strip_tags($this->_get_issue_label($issue)).'</option>'."\n";
			}
			$markup_string .= '</select>'."\n";
			$markup_string .= '<input type="submit" value="Go" />'."\n";
			$markup_string .= '</form>'."\n";
			$link = carl_make_link(array('issue_id' => 0));
			//$markup_string .= '<div class="allIssuesLink"><a href="'.$link.'">List all issues</a></div>';
			$markup_string .= '</div>'."\n";
		}
		return $markup_string;
	}
	
	function get_links_to_issues()
	{
		$links = array();
		if($this->has_issues())
		{
			$issues =& $this->get_visible_issues();
			foreach($issues as $issue_id => $issue_entity)
			{
				$links[$issue_entity->id()] = $this->get_link_to_issue($issue_id);
			}
		}
		return $links;
	}
	
	function _get_issue_label($issue)
	{
		$name = $issue->get_value('name');
		if(!empty($this->links_to_issues[$issue->id()]) )
		{
			$name = '<a href="'.$this->links_to_issues[$issue->id()].'">'.$name.'</a>';
			if ($this->params['show_datetime'])
			{
				$date = prettify_mysql_datetime( $issue->get_value( 'datetime' ), $this->date_format);
				$name = $name.' <span class="date">('.$date.')</span>';
			}
		}
		return $name;
	}
	
	function get_current_issue()
	{
		if(empty($this->request['issue_id']) && !empty($this->issue_id))
		{
			$link_args['issue_id'] = $this->issue_id;
		}
		if($this->has_issues() && !empty($this->issue_id))
		{
			$issues =& $this->get_visible_issues();
			return $issues[$this->issue_id];
		}
		else
			return false;
	}
	
	function has_issues()
	{
		if($this->publication && $this->publication->get_value('has_issues') == "yes")
		{
			$issues =& $this->get_visible_issues();
			if(!empty($issues)) return true;
		}
		return false;
	}
}
?>
