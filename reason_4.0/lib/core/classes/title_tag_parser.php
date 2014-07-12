<?php
/**
 * Fills in title tag patterns
 *
 * @package reason
 * @subpackage classes
 */
 
/**
 * Include the reason libraries & setup
 */
// include_once('reason_header.php');

/**
 * Fixes entities that do not have records in all their tables
 *
 * Amputees are entities that do not have records in all of their tables. 
 * Amputees are generally invisible to Reason, since entities are grabbed all-at-once
 * (Though for performance reasons entities are sometimes *not* grabbed with their tables, 
 * so amputees in your database can cause inconsistent behavior)
 *
 * This class creates the records necessary for entities to have records in all the appropriate tables.
 *
 * Example usage ...
 *
 * Example usage ...
 */
class TitleTagParser
{
  public $tags = array('organization_name', 'minisite_name', 'item_name', 'page_title');

  public function TitleTagParser ($pattern, $context)
  {
    $this->pattern = $pattern;
    $this->context = $context;
  }

  public function render ()
  {
    return reason_htmlspecialchars(strip_tags(array_reduce($this->tags, array('TitleTagParser', 'sub'), $this->pattern)));
  }

  protected function sub ($carry, $item)
  {
    return str_replace("[$item]", $this->$item(), $carry);
  }

  protected function organization_name ()
  {
    return FULL_ORGANIZATION_NAME;
  }

  protected function minisite_name ()
  {
    return $this->context->site_info->get_value('name');
  }

  protected function item_name ()
  {
    return 'it is an itmem';
  }

  protected function page_title ()
  {
    return 'pag titel';
  }
}
