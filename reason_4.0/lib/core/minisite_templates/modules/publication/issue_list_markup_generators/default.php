<?php
/**
 *  @package reason
 *  @subpackage minisite_modules
 */

/**
 * Include parent class
 */
reason_include_once( 'minisite_templates/modules/publication/markup_generator.php' );
	
/**
*  Generates the markup to display a list of issues.
*
*  Helper class to the publication minisite module.  
*
*  @author Nathan White
*/
class PublicationIssueListMarkupGenerator extends PublicationMarkupGenerator
{
	//yep, we're overloading a private variable from the abstract class  
	//Any children of this should extemd get_variables_needed instead of overloading this array.  
	var $variables_needed = array(  'issues_by_date',
									'links_to_issues',
									);

	function PublicationIssueListMarkupGenerator()
	{
	}

	function run()
	{	
		$this->markup_string .= $this->get_pre_list_markup();
		$this->markup_string .= $this->get_list_markup();
		$this->markup_string .= $this->get_post_list_markup();
	}

	function get_pre_list_markup()
	{
		$markup_string = '';
		return $markup_string;
	}
	
	function get_post_list_markup()
	{
		$markup_string = '';
		return $markup_string;
	}

/////
//  List item methods
/////
	
	function get_list_markup()
	{
		$issues_by_date = $this->passed_vars['issues_by_date'];
		$links_to_issues = $this->passed_vars['links_to_issues'];
		
		$markup_string = '<h3>Issue List</h3>';
		if (count($issues_by_date) > 0)
		{
			$markup_string .= '<ul class="issueList">'."\n";
			foreach ($issues_by_date as $id => $issue)
			{
				$markup_string .= '<li><a href="'.$links_to_issues[$id].'">'.strip_tags($issue->get_value('name')).'<a/></li>'."\n";
			}	
			$markup_string .= '</ul>' . "\n";
		}
		else
		{
			$markup_string .= '<p>There are no issues to list for this publication.</p>';
		}
		return $markup_string;
	}
}
?>
