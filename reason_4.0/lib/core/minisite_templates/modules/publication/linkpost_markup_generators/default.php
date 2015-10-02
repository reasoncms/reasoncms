<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
/**
 * Include parent class
 */
reason_include_once( 'minisite_templates/modules/publication/markup_generator.php' );

/**
 * special case markup generator...if a news/post has a linkpost_url it's just a pointer to another
 * area (maybe another reason page, maybe an article on nytimes.com, etc.). By default, the publication module
 * will automatically bounce a user directly to the article if they attempt to view it by /pubname?story_id=x.
 * But, if the url is /pubname?story_id=x&show_linkpost_url=yes, the module does NOT redirect and instead shows 
 * the user a clickable link.
 *
 * This markup generator is what drives that link. It will replace the "content" of the entity so that it can
 * be slotted into existing publications with "item" markup generators and have a reasonable look and feel.
 *
 * Publications that wish to make use of linkpost functionality should obviously give it a test to make sure it
 * functions the way they want, but this is reasonable base behavior.
 *
 * TODO -- make sure this works in related mode? does it matter?
 *		N/A, this is only used when showing the actual item, not when showing lists of headlines, so
 *		only need to be in general markup_generator_info
 * TODO - make sure this doesn't screw up inline editing too badly.
 *		N/A, can't edit this stuff in any event.
 *
 *  @author Tom Feiler
 */
class PublicationsLinkPostMarkupGenerator extends PublicationMarkupGenerator
{
	protected $item;
	public $variables_needed = array('item');

	function additional_init_actions()
	{
		$this->item = $this->passed_vars['item'];
	}

	function run()
	{
		// if there is an associated organization we want to display that...
		$orgDetails = "";
		$urlOrgs = $this->item->get_left_relationship('news_post_to_url_organization');
		if (count($urlOrgs) == 1) {
			$urlOrg = $urlOrgs[0];
			$orgDetails = ' <em class="org">' . $urlOrg->get_value("name") . '</em>';
		}

		$this->markup_string = "<span class=\"linkpost_output\"><a target=\"_blank\" href=\"" . $this->item->get_value("linkpost_url") . "\">Full Post</a>" . $orgDetails . "</span>";
	}

}
