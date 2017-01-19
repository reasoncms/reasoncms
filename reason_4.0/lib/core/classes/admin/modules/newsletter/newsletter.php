<?php

/**
 * This file contains the controller for NewsletterModule,
 * a newsletter creation interface for reason users, and some
 * utility functions written to make the export process easier.
 * It includes the other relevant files.
 * 
 * NewsletterModule consists of a form controller, six form steps,
 * an exporter class, and a publication factory class.
 * 
 * 
 * @author Andrew Bacon
 * @author Nathan White
 * @package reason
 * @subpackage admin
 */

/**
 * We're including the necessary files here. ...Duh.
 */
include_once(DISCO_INC . 'controller.php');
reason_include_once('classes/calendar.php');
reason_include_once('classes/admin/modules/default.php');
reason_include_once('classes/admin/modules/newsletter/SelectIncludes.php');
reason_include_once('classes/admin/modules/newsletter/SelectItems.php');
reason_include_once('classes/admin/modules/newsletter/SelectTemplate.php');
reason_include_once('classes/admin/modules/newsletter/EditNewsletter.php');
reason_include_once('classes/admin/modules/newsletter/ComposeEmail.php');
reason_include_once('classes/admin/modules/newsletter/Finished.php');
reason_include_once('helpers/publication_helper.php');
reason_include_once('classes/event_helper.php');
include_once('carl_util/tidy/tidy.php');
//include_once('carl_util/basic/email_funcs.php');
include_once('tyr/email.php');


/**
 * ???
 * 
 * 
 * 
 * 
 * 
 * @author Nathan White
 */
class PubHelperFactory
{
    function get_entity( &$row )
    {
        $entity = new PublicationHelper($row['id']);
        return $entity;
    }
}


/**
 * NewsletterModule provides a newsletter creation and emailing
 * interface to reason users. It can also prepopulate the newsletter
 * with information from events or publications attached to the site.
 * 
 * NewsletterModule consists of a form controller, six form steps,
 * an exporter class, and a publication factory class.
 * 
 */

class NewsletterModule extends DefaultModule
{
	function NewsletterModule (&$page)
	{
		$this->admin_page =& $page;
	}
	function init()
	{
		$this->head_items->add_javascript(REASON_HTTP_BASE_PATH . 'js/newsletter.js');
		$this->head_items->add_stylesheet(REASON_HTTP_BASE_PATH . 'css/reason_admin/newsletter.css');

		////////// THIS IS A HACK! /////////
		$pageTitles = array(
			'SelectIncludes' => 'Newsletter Builder &mdash; Step One',
			'SelectItems' => 'Newsletter Builder &mdash; Step Two',
			'SelectTemplate' => 'Newsletter Builder &mdash; Step Three', 
			'EditNewsletter' => 'Newsletter Builder &mdash; Step Four',
			'ComposeEmail' => 'Newsletter Builder &mdash; Step Five',
		);
		
		if (!empty($_REQUEST['newsletterIsFinished']))
		{
			$this->admin_page->title = 'Newsletter Builder &mdash; Complete';
		}
		elseif (isset($_REQUEST['_step']) && isset($pageTitles[$_REQUEST['_step']]))
		{
			$this->admin_page->title = $pageTitles[$_REQUEST['_step']];
			return;
		}
		///////// End of hack here /////////
	}


	function run()
	{
	
		$nametag = $this->admin_page->user_id;
		$face = $this->admin_page->authenticated_user_id;



		if (empty($_REQUEST['site_id'])) 
		{
			echo "Please select a site to get started.";
			return;
		}
		if (!empty($_REQUEST['newsletterIsFinished']))
		{
			echo "<h1>Newsletter sent!</h1>";
			echo "<h2>Process complete.<h2>";
			echo '<p><a href="'.$this->admin_page->make_link(array('newsletterIsFinished'=>'')).'">Send another newsletter</a></p>'."\n";
			return;
		}
	    $controller = new FormController;
	    $controller->set_session_class('Session_PHP');
	    $controller->set_session_name('REASON_SESSION');
	    $controller->set_data_context('newsletter_maker_'.$this->admin_page->site_id);
	    $controller->show_back_button = true;
	    $controller->clear_form_data_on_finish = true;
	    $controller->allow_arbitrary_start = false;
		$controller->authenticated_user_id = $face;
		$controller->user_id = $face;
		if ($nametag != $face)
		{
			if (reason_user_has_privs($face, 'pose_as_other_user'))
				$controller->user_id = $nametag;
		}
	    
	    // Set up the progression of forms.
	    $forms = array(
		    'SelectIncludes' => array(
			    'start_step' => true,
			    'next_steps' => array(
				    'SelectItems' => array(
					    'label' => 'Continue',
				    ),
			    ),
			    'step_decision' => array(
				    'type' => 'user',
			    ),
		    ),
		    'SelectItems' => array(
			    'next_steps' => array(
				    'SelectTemplate' => array(
					    'label' => 'Continue',
				    ),
			    ),
			    'step_decision' => array(
				    'type' => 'user',
			    ),
		    ),
		    'SelectTemplate' => array(
			    'next_steps' => array(
				    'EditNewsletter' => array(
					    'label' => 'Continue',
				    ),
			    ),
			    'step_decision' => array(
				    'type' => 'user',
			    ),
		    ),
		    'EditNewsletter' => array(
			    'next_steps' => array(
				    'ComposeEmail' => array(
					    'label' => 'Continue',
				    ),
			    ),
			    'step_decision' => array(
				    'type' => 'user',
			    ),
		    ),
		    'ComposeEmail' => array(
   			    'final_step' => true,
		    ),
	    );

	    // Add, init, and run the forms. 
	    $controller->add_forms( $forms );
	    $controller->init();
	    $controller->preserve_query_string = true;
	    $controller->run();

			    
	}
}


/**
 * Takes get_all_form_data() and turns it into something sensible.
 * 
 * Assemble_data expects an array from get_all_form_data(). Good 
 * luck hacking this, as the format is pretty specific. It expects data from a form that looks like this:
 * 
 * <code>
 * 	var $elements = array(
 *		'newsletter_title' => array(
 *		    'display_name' => 'Newsletter Title',
 *		    'type' => 'text',
 *		),
 *		'newsletter_intro' => array(
 *		    'display_name' => 'Write a message to precede the body of the newsletter',
 *		    'type' => 'textarea',
 *		),
 *	);
 * $publication_checkboxes = array(
 *		id_of_post . '_post' => 'display name of this post',
 *		id_of_another_post . '_post' => 'display name of another post',
 * );
 * $this->add_element('pub_posts_group_' . this_publication_id, 'checkboxgroup', array('options'=>$publication_checkboxes);
 * $events_checkboxes = array(
 * 		id_of_the_event . '_event' => 'display name of some event',
 * 		id_of_another_event . '_event' => 'display name of another event'
 * );
 * $this->add_element('events_group_' . date_of_these_events, 'checkboxgroup', $events_checkboxes);
 * </code>
 * 
 * The output of get_all_form_data() for such a form ends up looking 
 * like this (except it generally has a lot of garbage too):
 * 
 * <code>
 * array
 *   'newsletter_title' => string 'A newsletter title'
 *   'newsletter_intro' => string 'Just do the best you can -- but be sure it's your very best.'
 *   'pub_posts_group_556743' => 
 *        array
 *          0 => string '632296_post'
 *          1 => string '635739_post'
 *          2 => string '628269_post'
 *   'pub_posts_group_642174' => boolean false
 *   'events_header' => null
 *   'events_number' => null
 *   'events_group_day_2010-04-14' => 
 *     array
 *       0 => string '618385_event'
 *       1 => string '618389_event'
 * </code>
 * 
 * The output of assemble_data() then transforms this into something
 * meaningful. The same dump after assemble_data():
 * <code>
 *  array
 *   'info' => 
 *     array
 *       'title' => string 'A newsletter title'
 *       'intro' => string 'Just do the best you can -- but be sure it's your very best.'
 *       'urls' => 
 *         array
 *           'Marlena's Blog' => string 'https://bacon.test.carleton.edu/admissions/blogs/marlena/'
 *   'pubs' => 
 *     array
 *       'Marlena's Blog' => 
 *         array
 *           0 => 
 *             some post entity
 *           1 => 
 *             some post entity
 *           2 => 
 *             some post entity
 *   'events' => 
 *     array
 *       '2010-04-14' => 
 *         array
 *           0 => 
 *             some event entity
 *           1 => 
 *             some event entity
 * </code>
 * Much better, don't you think?
 * 
 * @param array $dump The output of get_all_form_data
 * @return array An array with the format described above.
 */

function assemble_data($dump)
{
	$assembled_data['info'] = array(
		'title' => htmlspecialchars($dump['newsletter_title']),
		'intro' => preg_replace("/\n/i", "<br />", htmlspecialchars($dump['newsletter_intro'])),
	);
	foreach ($dump as $item=>$value)
	{
		if (is_array($value))
		{
			if (preg_match("/^pub_posts_group_/", $item) != 0)
			{
				$pub_entity_id = preg_replace("/^pub_posts_group_/", '', $item);
				$ph = new PublicationHelper($pub_entity_id);
				$pub_page = $ph->get_right_relationship('page_to_publication');
				$pub_url = reason_get_page_url($pub_page[0]->get_value('id'));
				$pub_id = $ph->get_value('id');
				$assembled_data['info']['urls'][$pub_id] = $pub_url;
				foreach ($value as $key => $pub_post_id)
				{
					$entity_id = preg_replace('/_post$/', '', $pub_post_id);
					$assembled_data['pubs'][$pub_id][] = new entity($entity_id);
				}
			}
			if (preg_match("/^events_group_day_/", $item) != 0)
			{
				foreach ($value as $key => $event_option_name)
				{
					$entity_id = preg_replace('/_event$/', '', $event_option_name);
					$date = preg_replace('/^events_group_day_/', '', $item);
					$date = preg_replace('/_.*/', '', $date);
					$assembled_data['events'][$date][] = new entity($entity_id);
				}
			}
		} 
	}
	if (!empty($assembled_data['pubs']))
		foreach ($assembled_data['pubs'] as $pub_id => $posts)
		{
			usort($posts, "sort_pubs");
			$assembled_data['pubs'][$pub_id] = $posts;
		}
	if (!empty($assembled_data['events']))
		foreach ($assembled_data['events'] as $date=>$events)
		{
			usort($events, "sort_events");
			$assembled_data['events'][$date] = $events;
		}
	return $assembled_data;
}


/**
 * Comparison function for sorting publication items (posts) in reverse-chronological order (newest items at top).
 * 
 * @param object $a the first object to be compared.
 * @param object $b the second object to be compared.
 */
function sort_pubs($a, $b)
{
$first_date = strtotime($a->get_value('datetime'));
	$second_date = strtotime($b->get_value('datetime'));
    if ($first_date == $second_date) {
        return 0;
    }
    return ($first_date < $second_date) ? 1 : -1;
}


/**
 * Comparison function for sorting events in chronological order (oldest items at top).
 * 
 * @param object $a the first object to be compared.
 * @param object $b the second object to be compared.
 */
function sort_events($a, $b)
{
$first_date = strtotime($a->get_value('datetime'));
	$second_date = strtotime($b->get_value('datetime'));
    if ($first_date == $second_date) {
        return 0;
    }
    return ($first_date < $second_date) ? -1 : 1;
}

/**
 * Takes an HTML string and converts it to plaintext.
 * 
 * Currently handles the following items:
 * <ul>
 * <li>Tabs</li>
 * <li>Newlines, breaks</li>
 * <li>Headings</li>
 * <li>Paragraphs</li>
 * <li>Unordered and ordered lists (needs a little work)</li>
 * <li>Horizontal rules</li>
 * <li>Links</li>
 * </ul>
 * 
 * @param $html a string of html
 * @return string $plaintext the content of the html string.
 */

function to_plaintext($html)
{
	$needles = array(
		"/\t/",										// tabs
		"/\n/",										// newlines
		"/<h[12][^>]*>(.*?)<\/h[12][^>]*>/ie",		// h1 - h2
		"/<h3[^>]*>(.*?)<\/h3[^>]*>/ie",			// h1 - h3
		"/<h[456][^>]*>(.*?)<\/h[456][^>]*>/ie",	// h3 - h6
		"/<p[^>]*>/i",								// p
		"/<[\/]?(ul|ol)[^>]*>/ie",					// ul, ol
		"/<li[^>]*>(.*?)<\/li[^>]*>/ie",			// li
		"/<hr[^>]*>/i",								// hr
		"/<a href=(?:\"|')(.*?)(?:\"|')[^>]*>(.*?)<\/a[^>]*>/i", // href
		"/\n\n+/i",									// two lb
	);

	$thimbles = array(
	' ',							// tabs
	'',								// newlines
	"strtoupper(\"\n\n\\1\n\n\")",	// h1 - h2
	"strtoupper(\"\n\n\\1\n\")",	// h3
	"ucwords(\"\n\n\\1\n\n\")",		// h3 - h6
	"\t",							// p
	"\n\n",							// ul, ol
	"\"* \\1\n\"",					// li
	"\n---------------------------------------------------------------------------\n\n",	// hr
	"\\2 \n (\\1) ",				//href
	"\n\n",							//two lb
	);

    $plaintext = trim(stripslashes($html));
    $plaintext = preg_replace($needles, $thimbles, $plaintext);
    $plaintext = strip_tags($plaintext);
    $plaintext = wordwrap($plaintext, 75);
    $plaintext = stripslashes($plaintext);
    return $plaintext;
}


/**
 * Logs emails sent with the newsletter builder. 
 * 
 * Uses the W3C Extended Log Format
 * 
 * @param string $tos the people 
 */
function log_email($tos, $from, $subject, $face)
{
	$writeString = '';
	$crlf = "\r\n";
	if(!file_exists(REASON_LOG_DIR . 'newsletter_builder.log'))
	{
		$writeString .= "#Version: 1.0" . $crlf . "#Fields: date time c-ip cs-username comment" . $crlf;
	}

	$date = date("d-M-Y");
	$time = date("H:i:s");
	// Not always reliable.
	$c_ip = $_SERVER["REMOTE_ADDR"];
	$cs_username = $face;
	$writeString .= $date . ' '
				 . $time . ' '
				 . $c_ip . ' '
				 . $cs_username . ' '
				 . '"Sent to: ' . addslashes($tos) . '; Subject: ' . addslashes($subject) . '"'
				 . $crlf;
	$logfile = fopen(REASON_LOG_DIR . 'newsletter_builder.log', 'a');
	fwrite($logfile, $writeString);
}
	
	


///////////////////////////////////////////////////////////////////////////

/**
 * This exporter class is a wrapper for some useful, repetitive export tasks,
 * and allows for new export types. 
 * 
 * Developers can extend this class with a new method and change the formats array.
 * It also paves the way for allowing a two-template system (i.e. one for posts, one for 
 * events).
 * 
 * Usage: <code> 
 * $dump = $this->controller->get_all_form_data();
 * $data = assemble_data($dump);
 * $exporter = new NewsletterExporter($data);
 * $exporter->export($format);
 * </code>
 *
 * You can also loop through every format, as is done on SelectTemplate, like this:
 * <code>
 * $dump = $this->controller->get_all_form_data();
 * $exporter = new NewsletterExporter(assemble_data($dump));
 * $formats = $exporter->get_export_formats();
 * foreach ($formats as $key => $info_array) 
 * 	echo $exporter->export($key);
 * </code>
 * 
 * The class expects data in the following format:
 * 
 * <code>
 *  array(
 *   'info' => array(
 *       'title' => "A newsletter title",
 *       'intro' => "Just do the best you can -- but be sure it's your very best.",
 *       'urls' => array(
 *           "some_pub_id" => "https://bacon.test.carleton.edu/admissions/blogs/marlena/"
 *       ),
 *   )
 *   'pubs' => array(
 *       "some_pub_id" => array(
 *           0 => 
 *             some post entity
 *           1 => 
 *             some post entity
 *           2 => 
 *             some post entity
 *        )
 *   )
 *   'events' => array(
 *       '2010-04-14' => array(
 *           0 => 
 *             some event entity
 *           1 => 
 *             some event entity
 *       )
 *   )
 * </code>
 * 
 *
 * For information on how to write export formats, see {@link NewsletterExporter::export()}.
 *
 * @author Andrew Bacon
 * @package reason
 * @subpackage classes
 */
class NewsletterExporter {
	/**
	 * Describes the available export formats.
	 *
	 * Contains entries of the form
	 * <code>
	 * 'format_id' => array(
	 *   'method' => 'the_name_of_the_export_method',
	 *   'name' => "A very pretty name for the export format."
	 * )
	 * </code>
	 * 
	 * @var array
	 */
	var $_available_export_formats = array(
		'headings_only' => array(
			'method' => '_export_headings_only',
			'name' => 'Headings only',
		),
		'headings_only_with_descriptions' => array(
			'method' => '_export_headings_only_with_descriptions',
			'name' => "Headings only, show descriptions",
		),
		'headings_only_events_by_date' => array(
			'method' => '_export_headings_only_events_by_date',
			'name' => 'Headings only, show events by date',
		),
		'events_by_date_with_descriptions' => array(
			'method' => '_export_with_descriptions_events_by_date',
			'name' => 'Headings and descriptions, show events by date',
		),
		'headings_only_events_by_month' => array(
			'method' => '_export_headings_only_events_by_month',
			'name' => 'Headings only, show events by month',
		),
	);


	var $_data;


	/**
	 * Constructor for the class
	 * 
	 * 
	 * @param array $data optional 
	 * @return string $plaintext the content of the html string.
	 */
	function NewsletterExporter($data = NULL)
	{
		if(reason_file_exists('config/newsletter/newsletter_exporter_modifier.php'))
		{
			reason_include_once('config/newsletter/newsletter_exporter_modifier.php');
			if(class_exists('LocalNewsletterExporterModifier'))
			{
				$modifier = new LocalNewsletterExporterModifier();
				$modifier->modify($this);
			}
			else
			{
				trigger_error('config/newsletter/newsletter_exporter_modifier.php should define a class named LocalNewsletterExporterModifier');
			}
		}
		if (!empty($data))
		{
			$this->set_data($data);
		}
	}

	/**
	 * Sets the data to be exported. 
	 * 
	 * @param array $data the data to be exported.
	 * @return true
	 */
	function set_data($data)
	{
		$this->_data = $data;
	}

	/**
	 * Gets the data to be exported. 
	 * 
	 * @return array $_data
	 */
	function get_data()
	{
		return $this->_data;
	}

	/**
	 * This is a wrapper function for the export process.
	 * 
	 * To create an exporter for this class, you must:
	 * <ul>
	 * <li>Add an entry to the {@link NewsletterExporter::$_available_export_formats} array</li>
	 * <li>write a method with one param, the result of $this->get_data(),
	 * and which returns something. </li>
	 *</ul>
	 * 
	 * @see NewsletterExporter::$_available_export_formats
	 * @link NewsletterExporter::_export_headings_only() An example of an export function.
	 * @param string $format the format to use in exporting.
	 */
	function export($format)
	{
		if (empty($this->_data))
		{
			trigger_error("Exporter class was not assigned data to export.", WARNING);
			return false;
		}
		$formats = $this->get_export_formats();
		if (array_key_exists($format, $formats))
		{
			$definition = $formats[$format];
			if(!empty($formats[$format]['method']))
			{
				$method_name = $formats[$format]['method'];
				if(method_exists($this,$method_name))
				{
					return $this->$method_name($this->get_data());
				}
				else
				{
					trigger_error('Method named in export format definition ('.$method_name.') does not exist on this class.');
					return false;
				}
			}
			elseif(!empty($formats[$format]['callback']))
			{
				if(is_callable($formats[$format]['callback']))
				{
					return call_user_func($formats[$format]['callback'], $this->get_data());
				}
				else
				{
					trigger_error('Callback specified in export format '.$format.' is not callable.');
					return false;
				}
			}
			else
			{
				trigger_error('Export format '.$format.' needs to have either a "method" or "callback" array member');
				return false;
			}
		}
		else
		{
			trigger_error("Selected format $format wasn't found", WARNING);
			return false;
		}
	}
	
	function add_export_format($key, $definition)
	{
		$this->_available_export_formats[$key] = $definition;
	}
	
	/**
	 * Returns the array of available export formats. 
	 * 
	 * @return array $_available_export_formats
	 */
	function get_export_formats() 
	{
		return $this->_available_export_formats;
	
	}
	
	/**
	 * An export function which displays title, intro, publication/event
	 * titles and dates.
	 * 
	 * Output looks like:
	 * <code>
	 * <h1>A Newsletter Title</h1>
	 * <p>A newsletter description blah blah</p>
	 * <h2>Recent News</h2>
	 * <h3>The name of a publication</h3>
	 * <ul>
	 * 	<li><a target="_blank" href="some_story_url">A post</a> (Wed, May 19 2010  9:23 am)</li>
	 * 	<li><a target="_blank" href="some_story_url">A post</a> (Wed, May 19 2010  9:23 am)</li>
	 * </ul>
	 * <h2>Upcoming Events</h2>
	 * <ul>
	 * 	<li><a target="_blank" href="some_event_url">An event</a> (4:30 pm on Thu, Apr 15 2010)</li>
	 * 	<li><a target="_blank" href="some_event_url">An event</a> (4:30 pm on Thu, Apr 15 2010)</li>
	 * </ul>
	 * </code>
	 * 
	 * @param array the data to be transformed.
	 * @return array the transformed data
	 */
	function _export_headings_only($data)
	{
		$output = "";
		if ($data['info']['title'])
			$output = '<h1>' . $data['info']['title'] . '</h1>';
		if ($data['info']['intro'])
			$output .= '<p>' . $data['info']['intro'] . '</p>';
		if (!empty($data['pubs'])) 
		{
			$output .= "<h2>Recent News</h2>";
			foreach ($data['pubs'] as $pub_id => $pub_posts)
			{
				$pub_ent = new entity($pub_id);
				$output .= '<h3>'.$pub_ent->get_value('name').'</h3>';
				$output .= "<ul>";
				foreach ($pub_posts as $pub_post)
				{
					$output .= '<li><a target="_blank" href="' . $data['info']['urls'][$pub_id] . "?story_id=" . $pub_post->get_value('id') . '">' . $pub_post->get_value('release_title') . '</a> (' . date("D, M j Y  g:i a", strtotime($pub_post->get_value('datetime')))  . ')</li>';
				}
				$output .= "</ul>";
			}
		}
		if (!empty($data['events'])) 
		{
			$output .= "<h2>Upcoming Events</h2>";
			$output .= "<ul>";
			foreach ($data['events'] as $day=>$events) foreach ($events as $event)
			{
				$eHelper = new EventHelper();
				@$eHelper->set_page_link($event);
				$eventURL = $event->get_value('url') . date("Y-m-d", strtotime($day));
				$output .= '<li><a target="_blank" href="' . $eventURL . '">' . $event->get_value('name') . '</a> (' . date("g:i a", strtotime(preg_replace('/^.*[^ ] /', '', $event->get_value('datetime')))) . ' on ' . date("D, M j Y", strtotime($day)) . ')</li>';
			}
			$output .= "</ul>";
		}
		return tidy($output);
	}

	/**
	 * An export function which displays title, intro, publication/event
	 * titles and dates. This exporter also shows descriptions of the post/
	 * event.
	 * 
	 * Output looks like:
	 * <code>
	 * <h1>A Newsletter Title</h1>
	 * <p>A newsletter description blah blah</p>
	 * <h2>Recent News</h2>
	 * <h3>The name of a publication</h3>
	 * <ul>
	 * 	<li class="has_description"><strong><a target="_blank" href="some_story_url">A post</a></strong> (Wed, May 19 2010  9:23 am)
	 * 	<p>This is a description of this story!</p></li>
	 * 	<li class="has_description"><a target="_blank" href="some_story_url">A post that has no description data</a> (Wed, May 19 2010  9:23 am)</li>
	 * </ul>
	 * <h2>Upcoming Events</h2>
	 * <ul>
	 * 	<li class="has_description"><a target="_blank" href="some_event_url">An event</a> (4:30 pm on Thu, Apr 15 2010)
	 * 	<p>This is an event description</p></li>
	 * 	<li class="has_description"><a target="_blank" href="some_event_url">An event with no desc. data</a> (4:30 pm on Thu, Apr 15 2010)</li>
	 * </ul>
	 * </code>
	 * 
	 * @param array the data to be transformed.
	 * @return array the transformed data
	 */
	function _export_headings_only_with_descriptions($data)
	{
		$output = "";
		if ($data['info']['title'])
			$output = '<h1>' . $data['info']['title'] . '</h1>';
		if ($data['info']['intro'])
			$output .= '<p>' . $data['info']['intro'] . '</p>';
		if (!empty($data['pubs'])) 
		{
			$output .= "<h2>Recent News</h2>";
			foreach ($data['pubs'] as $pub_id => $pub_posts)
			{
				$pub_ent = new Entity($pub_id);
				$output .= '<h3>'.$pub_ent->get_value('name').'</h3>';
				$output .= "<ul>";
				foreach ($pub_posts as $pub_post)
				{
					$output .= '<li class="has_description"><strong>' . '<a target="_blank" href="' . $data['info']['urls'][$pub_id] . "?story_id=" . $pub_post->get_value('id') . '">' . $pub_post->get_value('release_title') . '</a></strong> (' . date("D, M j Y  g:i a", strtotime($pub_post->get_value('datetime')))  . ')';
					$output .= trim($pub_post->get_value('description')) . '</li>';
				}
				$output .= "</ul>";
			}
		}
		if (!empty($data['events'])) 
		{
			$output .= "<h2>Upcoming Events</h2>";
			$output .= "<ul>";
			foreach ($data['events'] as $day=>$events) foreach ($events as $event)
			{
				$eHelper = new EventHelper();
				@$eHelper->set_page_link($event);
				$eventURL = $event->get_value('url') . date("Y-m-d", strtotime($day));
				$output .= '<li><a target="_blank" href="' . $eventURL . '">' . $event->get_value('name') . '</a> (' . date("g:i a", strtotime(preg_replace('/^.*[^ ] /', '', $event->get_value('datetime')))) . ' on ' . date("D, M j Y", strtotime($day)) . ')<br />' . $event->get_value('description') . '</li>';
			}
			$output .= "</ul>";
		}
		return tidy($output);
	}	
	
	/**
	 * An export function which displays title, intro, publication/event
	 * titles and dates, with events grouped by date.
	 * 
	 * Output looks like:
	 * <code>
	 * <h1>A Newsletter Title</h1>
	 * <p>A newsletter description blah blah</p>
	 * <h2>Recent News</h2>
	 * <h3>The name of a publication</h3>
	 * <ul>
	 * 	<li><a target="_blank" href="some_story_url">A post</a> (Wed, May 19 2010  9:23 am)</li>
	 * 	<li><a target="_blank" href="some_story_url">A post</a> (Wed, May 19 2010  9:23 am)</li>
	 * </ul>
	 * <h2>Upcoming Events</h2>
	 * <h3>Thu, April 15 2010</h3>
	 * <ul>
	 * 	<li><a target="_blank" href="some_event_url">An event</a> (4:30 pm)</li>
	 * 	<li><a target="_blank" href="some_event_url">An event</a> (5:30 pm)</li>
	 * </ul>
	 * <h3>Fri, April 16 2010</h3>
	 * <ul>
	 * 	<li><a target="_blank" href="some_event_url">An event</a> (4:30 pm)</li>
	 * 	<li><a target="_blank" href="some_event_url">An event</a> (5:30 pm)</li>
	 * </ul>
	 * </code>
	 * 
	 * @param array the data to be transformed.
	 * @return array the transformed data
	 */
	function _export_headings_only_events_by_date($data)
	{
		$output = "";
		if ($data['info']['title'])
			$output = '<h1>' . $data['info']['title'] . '</h1>';
		if ($data['info']['intro'])
			$output .= '<p>' . $data['info']['intro'] . '</p>';
		if (!empty($data['pubs'])) 
		{
			$output .= "<h2>Recent News</h2>";
			foreach ($data['pubs'] as $pub_id => $pub_posts)
			{
				$pub_ent = new Entity($pub_id);
				$output .= '<h3>'.$pub_ent->get_value('name').'</h3>';
				$output .= "<ul>";
				foreach ($pub_posts as $pub_post)
				{
					$output .= '<li><a target="_blank" href="' . $data['info']['urls'][$pub_id] . "?story_id=" . $pub_post->get_value('id') . '">' . $pub_post->get_value('release_title') . '</a> (' . date("D, M j Y  g:i a", strtotime($pub_post->get_value('datetime')))  . ')</li>';
				}
				$output .= "</ul>";
			}
		}
		if (!empty($data['events'])) 
		{
			$output .= "<h2>Upcoming Events</h2>";

			foreach ($data['events'] as $day=>$events) 
			{
				$output .= "<h3>" . date("D, F j Y", strtotime($day)) . "</h3>";
				$output .= "<ul>";			
				foreach ($events as $event)
				{
					$eHelper = new EventHelper();
					@$eHelper->set_page_link($event);
					$eventURL = $event->get_value('url') . date("Y-m-d", strtotime($day));
					$output .= '<li><a target="_blank" href="' . $eventURL . '">' . $event->get_value('name') . '</a> (' . date("g:i a", strtotime(preg_replace('/^.*[^ ] /', '', $event->get_value('datetime')))) . ')</li>';
				}
				$output .= "</ul>";	
			}

		}
		return tidy($output);
	}
	
	/**
	 * An export function which displays title, intro, publication/event
	 * titles, descriptions, and dates, with events grouped by date.
	 * 
	 * Output looks like:
	 * <code>
	 * <h1>A Newsletter Title</h1>
	 * <p>A newsletter description blah blah</p>
	 * <h2>Recent News</h2>
	 * <h3>The name of a publication</h3>
	 * <ul>
	 * 	<li><a target="_blank" href="some_story_url">A post</a> (Wed, May 19 2010  9:23 am)<br />
	 *  Description of news item</li>
	 * 	<li><a target="_blank" href="some_story_url">A post</a> (Wed, May 19 2010  9:23 am)<br />
	 *  Description of news item</li>
	 * </ul>
	 * <h2>Upcoming Events</h2>
	 * <h3>Thu, April 15 2010</h3>
	 * <ul>
	 * 	<li><a target="_blank" href="some_event_url">An event</a> (4:30 pm)<br />
	 *  Description of event</li>
	 * 	<li><a target="_blank" href="some_event_url">An event</a> (5:30 pm)<br />
	 *  Description of event</li>
	 * </ul>
	 * <h3>Fri, April 16 2010</h3>
	 * <ul>
	 * 	<li><a target="_blank" href="some_event_url">An event</a> (4:30 pm)<br />
	 *  Description of event</li>
	 * 	<li><a target="_blank" href="some_event_url">An event</a> (5:30 pm)<br />
	 *  Description of event</li>
	 * </ul>
	 * </code>
	 * 
	 * @param array the data to be transformed.
	 * @return array the transformed data
	 */
	function _export_with_descriptions_events_by_date($data)
	{
		$output = "";
		if ($data['info']['title'])
			$output = '<h1>' . $data['info']['title'] . '</h1>';
		if ($data['info']['intro'])
			$output .= '<p>' . $data['info']['intro'] . '</p>';
		if (!empty($data['pubs'])) 
		{
			$output .= "<h2>Recent News</h2>";
			foreach ($data['pubs'] as $pub_id => $pub_posts)
			{
				$pub_ent = new Entity($pub_id);
				$output .= '<h3>'.$pub_ent->get_value('name').'</h3>';
				$output .= "<ul>";
				foreach ($pub_posts as $pub_post)
				{
					$output .= '<li><a target="_blank" href="' . $data['info']['urls'][$pub_id] . "?story_id=" . $pub_post->get_value('id') . '">' . $pub_post->get_value('release_title') . '</a> (' . date("D, M j Y  g:i a", strtotime($pub_post->get_value('datetime')))  . ')<br />';
					$output .= trim($pub_post->get_value('description'));
					$output .= '</li>';
				}
				$output .= "</ul>";
			}
		}
		if (!empty($data['events'])) 
		{
			$output .= "<h2>Upcoming Events</h2>";

			foreach ($data['events'] as $day=>$events) 
			{
				$output .= "<h3>" . date("D, F j Y", strtotime($day)) . "</h3>";
				$output .= "<ul>";			
				foreach ($events as $event)
				{
					$eHelper = new EventHelper();
					@$eHelper->set_page_link($event);
					$eventURL = $event->get_value('url') . date("Y-m-d", strtotime($day));
					$output .= '<li><a target="_blank" href="' . $eventURL . '">' . $event->get_value('name') . '</a> (' . date("g:i a", strtotime(preg_replace('/^.*[^ ] /', '', $event->get_value('datetime')))) . ')<br />';
					$output .= trim($event->get_value('description'));
					$output .= '</li>';
				}
				$output .= "</ul>";	
			}

		}
		return tidy($output);
	}
	
	/**
	 * An export function which displays title, intro, publication/event
	 * titles and dates, with events grouped by month.
	 * 
	 * Output looks like:
	 * <code>
	 * <h1>A Newsletter Title</h1>
	 * <p>A newsletter description blah blah</p>
	 * <h2>Recent News</h2>
	 * <h3>The name of a publication</h3>
	 * <ul>
	 * 	<li><a target="_blank" href="some_story_url">A post</a> (Wed, May 19 2010  9:23 am)</li>
	 * 	<li><a target="_blank" href="some_story_url">A post</a> (Wed, May 19 2010  9:23 am)</li>
	 * </ul>
	 * <h2>Upcoming Events</h2>
	 * <h3>April 2010</h3>
	 * <ul>
	 * 	<li><a target="_blank" href="some_event_url">An event</a> (4:30 pm on Thu, Apr 15 2010)</li>
	 * 	<li><a target="_blank" href="some_event_url">An event</a> (4:30 pm on Fri, Apr 16 2010)</li>
	 * </ul>
	 * <h3>May 2010</h3>
	 * <ul>
	 * 	<li><a target="_blank" href="some_event_url">An event</a> (4:30 pm on Thu, May 15 2010)</li>
	 * 	<li><a target="_blank" href="some_event_url">An event</a> (4:30 pm on Fri, May 16 2010)</li>
	 * </ul>
	 * </code>
	 * 
	 * @param array the data to be transformed.
	 * @return array the transformed data
	 */
	function _export_headings_only_events_by_month($data)
	{
		$output = "";
		if ($data['info']['title'])
			$output = '<h1>' . $data['info']['title'] . '</h1>';
		if ($data['info']['intro'])
			$output .= '<p>' . $data['info']['intro'] . '</p>';
		if (!empty($data['pubs'])) 
		{
			$output .= "<h2>Recent News</h2>";
			foreach ($data['pubs'] as $pub_id => $pub_posts)
			{
				$pub_ent = new Entity($pub_id);
				$output .= '<h3>'.$pub_ent->get_value('name').'</h3>';
				$output .= "<ul>";
				foreach ($pub_posts as $pub_post)
				{
					$output .= '<li><a target="_blank" href="' . $data['info']['urls'][$pub_id] . "?story_id=" . $pub_post->get_value('id') . '">' . $pub_post->get_value('release_title') . '</a> (' . date("D, M j Y  g:i a", strtotime($pub_post->get_value('datetime')))  . ')</li>';
				}
				$output .= "</ul>";
			}
		}
		if (!empty($data['events'])) 
		{
			$output .= "<h2>Upcoming Events</h2>";

			foreach ($data['events'] as $day=>$events) 
			{
				$events_by_month[date("M-Y", strtotime($day))][$day] = $events;
			}
			foreach ($events_by_month as $month => $day)
			{
				$output .= "<h3>" . date("F Y", strtotime($month)) . "</h3>";
				$output .= "<ul>";
				foreach ($day as $day => $events)
				{
					foreach ($events as $event)
					{
						$eHelper = new EventHelper();
						@$eHelper->set_page_link($event);
						$eventURL = $event->get_value('url') . date("Y-m-d", strtotime($day));
						$output .= '<li><a target="_blank" href="' . $eventURL . '">' . $event->get_value('name') . '</a> (' . date("D, M j", strtotime($day)) . " at " . date("g:i a", strtotime(preg_replace('/^.*[^ ] /', '', $event->get_value('datetime')))) . ')</li>';
					}
				}
				$output .= "</ul>";	
			}

		}
		return tidy($output);
	}
}
?>
