<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

include_once( 'reason_header.php' );
include_once(TYR_INC . "tyr.php");
reason_include_once("minisite_templates/modules/form/models/thor.php");
include_once(INCLUDE_PATH . "/disco/plugins/akismet/akismet.php");

class DefaultPublicationEmailer extends Disco {
	private $site;
	private $publication;
	private $newsPost;
	private $link;

	const HONEYPOT_FIELD = "url";

	function DefaultPublicationEmailer($site, $publication, $newsPost, $link) {
		$this->site = $site;
		$this->publication = $publication;
		$this->newsPost = $newsPost;
		$this->link = $link;
	}

	private function getClientIp() {
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}

	private function logEvent($event) {
		$siteId = isset($_REQUEST["site_id"]) ? $_REQUEST["site_id"] : "?";
		$pageId = isset($_REQUEST["page_id"]) ? $_REQUEST["page_id"] : "?";
		$storyId = $this->get_value("passthru_story_id");
		if (empty($storyId)) { $storyId = "?"; }

		$logtext = sprintf('%s (%s): site=%s, page=%s, story=%s, %s"',
                    date('Y-m-d H:i:s'),
                    $this->getClientIp(),
					$siteId, $pageId, $storyId,
                    $event
                    );
		dlog($logtext, REASON_LOG_DIR . "publication_share_via_email.log");
	}

	function get_share_intro_text() {
		if ($this->show_form) {
			$rv = "<h3 class='email_share_intro'>Share the story \"" . $this->newsPost->get_value("release_title") . "\":</h3>";
		} else {
			$rv = "";
		}
		return $rv;
	}

	function hide_honeypot() {
		// we hide both the containing row and the input
		echo "<style>#urlElement,#urlItem{display:none;}</style>";
	}

	function on_every_time() {
		$thor = new ThorFormModel();

		$this->hide_honeypot();
		
		$this->set_box_class( 'StackedBox' );

		$this->set_actions(Array('Send Email'));

		$this->add_element('passthru_story_id', 'hidden');
		$this->set_value('passthru_story_id', $_REQUEST['story_id']);

		$this->add_element('your_name', 'text');
		$this->add_required('your_name');
		$this->set_value("your_name", $thor->get_full_name());

		$this->add_element('your_email', 'text');
		$this->add_required('your_email');
		$this->set_value("your_email", $thor->get_email());

		$this->add_element(DefaultPublicationEmailer::HONEYPOT_FIELD, 'text');
		$this->set_display_name(DefaultPublicationEmailer::HONEYPOT_FIELD, '');

		$this->add_element('recipient_email', 'text');
		$this->add_required('recipient_email');

		$this->add_element('personal_note', 'textarea');
		$this->set_display_name('personal_note', 'Enter a note to be included in your message');
		
		// Manually classifify form content for Akismet in hope of getting
		// better results from Akismet
		$akismet_filter = new AkismetFilter($this, array(
				"setCommentType" => "comment", // akismet value 
				"setCommentAuthor" => "your_name", // form field name
				"setCommentAuthorEmail" => "your_email",  // form field name
				"setCommentContent" => "personal_note", // form field name
			));
	}

	function run_error_checks() {
		if (!filter_var($this->get_value("recipient_email"), FILTER_VALIDATE_EMAIL)) {
			$this->set_error("recipient_email", "Please check the recipient email address");
		}

		if (!filter_var($this->get_value("your_email"), FILTER_VALIDATE_EMAIL)) {
			$this->set_error("your_email", "Please check your email address");
		}
	}

	function process() {
		$storyId = $this->get_value("passthru_story_id");
		if (empty($storyId)) {
			trigger_error("Error - missing story id!");
		} else {
			$link = $this->link;
			
			$your_name = substr(strip_tags($this->get_value("your_name")),0,100);
			
			$site_name = strip_tags($this->site->get_value("name"));
			
			if(defined('FULL_ORGANIZATION_NAME') && strlen(FULL_ORGANIZATION_NAME) > 0)
			{
				$site_name = FULL_ORGANIZATION_NAME . ' / ' . $site_name;
			}

			$story = new entity($storyId);
			$clientIp = $this->getClientIp();
			$htmlContents = htmlspecialchars($your_name) . " (" . htmlspecialchars($this->get_value("your_email")) . ", " . $clientIp . ") wants to share a story with you from ".$site_name.".<p>";
			$plainContents = $your_name . " (" . $this->get_value("your_email") . ", " . $clientIp . ") wants to share a story with you from ".$site_name."\n";
			if ($this->get_value("personal_note") != "") {
				$htmlContents .= "Personal Note: <em>\"" . reason_htmlspecialchars(strip_tags($this->get_value("personal_note"))) . "\"</em><p>";
				$plainContents .= "Personal Note:" . strip_tags($this->get_value("personal_note")) . "\n";
			}
			$htmlContents .= '<hr><br>';
			$plainContents .= "------------\n";
			$htmlContents .= '<strong><a href="'.$link.'">' . $story->get_value("release_title") . "</a></strong><br>" . $story->get_value("description") . "<br>";
			$plainContents .= strip_tags($story->get_value("release_title")) . "\n" . strip_tags($story->get_value("description")) . "\n";
			$htmlContents .= '<hr><br>';
			$plainContents .= "------------\n";
			$htmlContents .= "<a href=\"" . $link . "\">Click here to read the full story</a>";
			$plainContents .= "For the full story, visit: " . $link;

			$msg = Array(
				"to" => $this->get_value("recipient_email"),
				"subject" => $your_name . ' shared a story with you: "' . $story->get_value('release_title') . '"',
				"html_body" => $htmlContents,
				"body" => $plainContents
			);

			if ($this->get_value(DefaultPublicationEmailer::HONEYPOT_FIELD) != "") {
				// fail silently; honeypot had data entered into it...
				$this->logEvent($your_name . " / " . $this->get_value("your_email") . " attempted to send a message to " . $this->get_value("recipient_email") . " with note \"" . $this->get_value("personal_note") . "\"; honeypot violation detected (" . $this->get_Value(DefaultPublicationEmailer::HONEYPOT_FIELD) . ")");
			} else {
				// echo "<PRE>"; var_dump($msg); echo "</PRE>";
				$tyr = new Tyr(Array($msg));
				$tyr->run();
				$this->logEvent($your_name . " / " . $this->get_value("your_email") . " sent a message to " . $this->get_value("recipient_email") . " with note \"" . $this->get_value("personal_note") . "\"");
			}
			echo "Thanks for sharing! Your message has been sent.<p><a href=\"" . $link . "\">Go back to the story</a>";

			$this->show_form = false;
		}
	}
}
