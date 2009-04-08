<?php
/**
 * @package reason
 * @subpackage scripts
 *
 * @todo this script should probably move into lib/core/scripts, leaving just a stub here
 */
include_once('reason_header.php');
reason_include_once( 'classes/quote_helper.php' );

$site_id = (!empty($_REQUEST['site_id'])) ? $_REQUEST['site_id'] : '';
$page_id = (!empty($_REQUEST['page_id'])) ? $_REQUEST['page_id'] : '5';

$cleanup_rules = array('site_id' => array('function' => 'turn_into_int'),
					   'page_id' => array('function' => 'turn_into_int'),
					   'page_category_mode' => array('function' => 'turn_into_int'),
					   'prefer_short_quotes' => array('function' => 'turn_into_int'),
					   'cache_lifespan' => array('function' => 'turn_into_int'),
					   'viewed_quote_ids' => array('function' => 'populate_viewed_quote_ids'));

$request = carl_clean_vars($_REQUEST, $cleanup_rules);

$qh = new QuoteHelper();

if (isset($request['site_id'])) $qh->set_site_id($request['site_id']);
if (isset($request['page_id'])) $qh->set_page_id($request['page_id']);
if (isset($request['cache_lifespan'])) $qh->set_cache_lifespan($request['cache_lifespan']);
if (isset($request['page_category_mode'])) $qh->set_page_category_mode($request['page_category_mode']);
if (isset($request['viewed_quote_ids'])) $qh->set_unavailable_quote_ids($request['viewed_quote_ids']);

// this should be able to support quotes when not in random mode as well probably
$qh->init();
$quote =& $qh->get_random_quote();

if (!empty($quote))
{
	$prefer_short_quotes = (isset($request['prefer_short_quotes'])) ? ($request['prefer_short_quotes']) : false;
	$short_description = ($prefer_short_quotes) ? $quote->get_value('description') : '';
	$quote_id = $quote->id();
	$quote_text = ($short_description) ? $short_description : $quote->get_value('content');
	$quote_author = ($quote->get_value('author')) ? $quote->get_value('author') : '';
	$quote_divider = ($quote->get_value('quote_divider')) ? $quote->get_value('quote_divider') : '';
}

// if we have a quote id and quote text then return the xml chunk
if (!empty($quote_id) && !empty($quote_text))
{
	header('Content-type: text/xml');
	echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
	echo '<quote>'."\n";
	echo '<id>'.$quote_id.'</id>'."\n";
	echo '<text>'.$quote_text.'</text>'."\n";
	echo '<author>'.$quote_author.'</author>'."\n";
	echo '<divider>'.$quote_divider.'</divider>'."\n";
	echo '</quote>';
}

// function to explode the posted string of viewed quote ids into an array
function populate_viewed_quote_ids($x)
{
	$quote_ids = explode(",",$x);
	if (!empty($quote_ids))
	{
		foreach($quote_ids as $k=>$id)
		{
			$clean_id = turn_into_int($id);
			if (!empty($clean_id))
			{
				$clean_quote_ids[$k] = $clean_id;
			}
		}
	}
	if (!empty($clean_quote_ids)) return $clean_quote_ids;
}
?>