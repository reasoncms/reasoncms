<?php
/**
 * @package reason_local
 * @subpackage minisite_modules
 */

/**
 * Include the reason header, and register the module with Reason
 */
include_once( 'reason_header.php' );

/**
 * Include dependencies
 */
reason_include_once( 'minisite_templates/modules/profile/forms/default.php' );

/**
 * Profile Module tags edit form.
 *
 * - Fancy Ajaxy tag editing
 *
 * @author Mark Heiman
 */
abstract class tagsBaseProfileEditForm extends defaultProfileEditForm
{
	protected $max_chars = 45;
	protected $max_words = 4;
	public $elements = array(
		'tags' => array(
			'type'=>'text',
			'display_name'=>'&nbsp;',
			)
		);
	public $show_error_jumps = false;
	public $instructions = 'Enter one or two word descriptions. Type enter or tab to end a tag. <span class="smallText"><br />(You can
		choose the tags suggested as you type or enter your own.)</span>';
	
	function custom_init()
	{
		if($head_items = $this->get_head_items())
		{
			$head_items->add_javascript(WEB_JAVASCRIPT_PATH . 'jquery.reasonAjax.js');
			$head_items->add_javascript(JQUERY_UI_URL, true);
			$head_items->add_javascript(JQUERY_URL, true);
			$head_items->add_stylesheet(JQUERY_UI_CSS_URL);
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH .'modules/profiles/jquery.tagit.css');
			$head_items->add_javascript(REASON_HTTP_BASE_PATH .'modules/profiles/tag_autosuggest.js');
			$head_items->add_javascript(REASON_HTTP_BASE_PATH .'modules/profiles/tag-it.js');
		}
	}
	
	function pre_show_form()
	{
		// update the tag field here (rather than in init) so that changes are visible on save
		if (!$this->has_errors())
		{
			$existing = $this->person->get_categories($this->section);
			$this->set_value('tags', join('; ', $existing));
		}
		echo '<p class="instructions">'.$this->instructions.'</p>';
	}

	function run_error_checks()
	{
		if ($tag_list = $this->get_value('tags'))
		{
			$tags = preg_split('/\s*;\s*/', $tag_list);
			foreach ($tags as $tag)
			{
				if (strlen($tag) > $this->max_chars)
					$this->set_error('tags', 'Tags should be no more than '.$this->max_chars.' characters; 
						<strong>'.$tag.'</strong> is '.strlen($tag). ' characters.');
				$words = count(explode(' ', $tag));
				if ($words > $this->max_words)
					$this->set_error('tags', 'Tags should be no more than '.$this->max_words.' words; 
						<strong>'.$tag.'</strong> is '.$words. ' words.');
					
				$slug = preg_replace(array('/[\s\/]+/','/[^a-z0-9_-]/'), array('_',''), strtolower(trim($tag)));
				if (empty($slug))
					$this->set_error('tags', 'Sorry, <strong>'.$tag.'</strong> is not usable as an
						interest tag. In order to make tags as broadly useful as possible, please
						use the roman alphabet.');
			}
		}
	}
	
	/**
	 * Save / update tags
	 */
	function process()
	{
		$existing = $this->person->get_categories($this->section);

		if ($tag_list = $this->get_value('tags'))
		{
			$tags = preg_split('/\s*;\s*/', $tag_list);
			foreach ($tags as $tag)
			{
				// Generate a slug by replacing spaces with underscores and stripping out everything but a-z0-9_-
				if ($slug = preg_replace(array('/[\s\/]+/','/[^a-z0-9_-]/'), array('_',''), strtolower(trim($tag))))
				{
					if (isset($existing[$slug]))
					{
						unset($existing[$slug]);
					} else {
						$this->person->set_category($slug, $tag, $this->section);
					}
				}
			}
		}
		
		// If there's anything left here, it's tags they've deleted
		if (count($existing))
		{
			foreach ($existing as $slug => $name) $this->person->remove_category($slug, $this->section);
		}
		
	}
	
}