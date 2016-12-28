<?php

/**
 * @package reason
 * @subpackage content_managers
 */
reason_include_once('content_managers/default.php3');
reason_include_once('classes/media/factory.php');
require_once(SETTINGS_INC . 'media_integration/media_settings.php');

/**
 * Register the content manager with Reason
 */
$GLOBALS['_content_manager_class_names'][basename(__FILE__)] = 'av_captions';

/**
 * A content manager for Media Captions (which live with Media Works)
 */
class av_captions extends ContentManager
{

	var $box_class = 'stackedBox';
	var $fields_to_remove = array();
	var $field_order = array('av', 'upload', 'content', 'content_manual', 'kind', 'lang', 'label', 'unique_name', 'name',);
	var $inited = false;
	var $languages = array(
		// A ISO-639-1 language code list  
		"" => "",
		"en" => "English",
		"es" => "Spanish",
		"ab" => "Abkhazian",
		"aa" => "Afar",
		"af" => "Afrikaans",
		"sq" => "Albanian",
		"am" => "Amharic",
		"ar" => "Arabic",
		"hy" => "Armenian",
		"as" => "Assamese",
		"ay" => "Aymara",
		"az" => "Azerbaijani",
		"ba" => "Bashkir",
		"eu" => "Basque",
		"bn" => "Bengali",
		"dz" => "Bhutani",
		"bh" => "Bihari",
		"bi" => "Bislama",
		"br" => "Breton",
		"bg" => "Bulgarian",
		"my" => "Burmese",
		"be" => "Byelorussian",
		"km" => "Cambodian",
		"ca" => "Catalan",
		"zh" => "Chinese",
		"co" => "Corsican",
		"hr" => "Croatian",
		"cs" => "Czech",
		"da" => "Danish",
		"nl" => "Dutch",
		"en" => "English",
		"eo" => "Esperanto",
		"et" => "Estonian",
		"fo" => "Faeroese",
		"fj" => "Fiji",
		"fi" => "Finnish",
		"fr" => "French",
		"fy" => "Frisian",
		"gl" => "Galician",
		"ka" => "Georgian",
		"de" => "German",
		"el" => "Greek",
		"kl" => "Greenlandic",
		"gn" => "Guarani",
		"gu" => "Gujarati",
		"ha" => "Hausa",
		"he" => "Hebrew",
		"hi" => "Hindi",
		"hu" => "Hungarian",
		"is" => "Icelandic",
		"id" => "Indonesian",
		"ia" => "Interlingua",
		"ie" => "Interlingue",
		"ik" => "Inupiak",
		"iu" => "Inuktitut (Eskimo)",
		"ga" => "Irish",
		"it" => "Italian",
		"ja" => "Japanese",
		"jw" => "Javanese",
		"kn" => "Kannada",
		"ks" => "Kashmiri",
		"kk" => "Kazakh",
		"rw" => "Kinyarwanda",
		"ky" => "Kirghiz",
		"rn" => "Kirundi",
		"ko" => "Korean",
		"ku" => "Kurdish",
		"lo" => "Laothian",
		"la" => "Latin",
		"lv" => "Latvian, Lettish",
		"ln" => "Lingala",
		"lt" => "Lithuanian",
		"mk" => "Macedonian",
		"mg" => "Malagasy",
		"ms" => "Malay",
		"ml" => "Malayalam",
		"mt" => "Maltese",
		"mi" => "Maori",
		"mr" => "Marathi",
		"mo" => "Moldavian",
		"mn" => "Mongolian",
		"na" => "Nauru",
		"ne" => "Nepali",
		"no" => "Norwegian",
		"oc" => "Occitan",
		"or" => "Oriya",
		"om" => "Oromo",
		"ps" => "Pashto, Pushto",
		"fa" => "Persian",
		"pl" => "Polish",
		"pt" => "Portuguese",
		"pa" => "Punjabi",
		"qu" => "Quechua",
		"rm" => "Rhaeto-Romance",
		"ro" => "Romanian",
		"ru" => "Russian",
		"sm" => "Samoan",
		"sg" => "Sangro",
		"sa" => "Sanskrit",
		"gd" => "Scots Gaelic",
		"sr" => "Serbian",
		"sh" => "Serbo-Croatian",
		"st" => "Sesotho",
		"tn" => "Setswana",
		"sn" => "Shona",
		"sd" => "Sindhi",
		"si" => "Singhalese",
		"ss" => "Siswati",
		"sk" => "Slovak",
		"sl" => "Slovenian",
		"so" => "Somali",
		"es" => "Spanish",
		"su" => "Sudanese",
		"sw" => "Swahili",
		"sv" => "Swedish",
		"tl" => "Tagalog",
		"tg" => "Tajik",
		"ta" => "Tamil",
		"tt" => "Tatar",
		"te" => "Tegulu",
		"th" => "Thai",
		"bo" => "Tibetan",
		"ti" => "Tigrinya",
		"to" => "Tonga",
		"ts" => "Tsonga",
		"tr" => "Turkish",
		"tk" => "Turkmen",
		"tw" => "Twi",
		"ug" => "Uigur",
		"uk" => "Ukrainian",
		"ur" => "Urdu",
		"uz" => "Uzbek",
		"vi" => "Vietnamese",
		"vo" => "Volapuk",
		"cy" => "Welch",
		"wo" => "Wolof",
		"xh" => "Xhosa",
		"yi" => "Yiddish",
		"yo" => "Yoruba",
		"za" => "Zhuang",
		"zu" => "Zulu",
	);

	function on_every_time()
	{
		$this->add_element('content_manual', 'textarea', array('display_name' => 'Manually Edit Track Content'));

		$this->set_order($this->field_order);
	}

	function pre_error_check_actions()
	{
		// If content was manually added/edited, use it
		$manual_edits = trim($this->get_value('content_manual'));
		if ($manual_edits) {
			$this->set_value('content', $manual_edits);
		}

		
		// Until we know we want to permit <script> tags
		// in the WebVTT captions we store, pull them out here.
		// The content is delivered raw (not htmlencoded) to users
		// since the track content can contain html. 
		$content = $this->get_value('content');
		$content_no_scripts = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $content);
		$this->set_value('content', $content_no_scripts);

		// Generate or update the 'label' and 'name' fields
		$pretty_lang = $this->languages[$this->get_value('lang')];

		$generated_label = "$pretty_lang " . $this->get_value('kind');
		$this->set_value('label', $generated_label);

		$av_id = $this->get_value('av');
		if ($av_id > 0) {
			$media_work = new entity($av_id);
			$media_work_name = $media_work->get_value('name');
		} else {
			$media_work_name = "Unknown";
		}
		$generated_name = "$pretty_lang {$this->get_value('kind')} for $media_work_name";
		$this->set_value('name', $generated_name);
	}

	function alter_data()
	{
		
		$this->add_relationship_element('av', id_of('av'), relationship_id_of('av_to_av_captions'), 'left', 'select');
		$this->set_display_name('av', 'Media Work');
		$this->add_required('av');
		
		// Apply a few defaults for new entities
		if ($this->_is_first_time() && !$this->has_errors() && $this->is_new_entity() && !$this->get_value('name')) {
			$this->set_value('lang', 'en');

			// Prefill the media work ID when creating a new caption
			// in the context of a Media Work.
			if (!empty($this->admin_page->request['__old_id'])) {
				$this->set_value('av', $this->admin_page->request['__old_id']);
			}
		}

		// We're going to dynamically generate 'name' and 'label' at save
		// until we have a reason to make users type in a label.
		// The generated label & name will keep the front end and back end
		// fields consistent
		$this->remove_required('name');
		$this->change_element_type('name', 'hidden', array('userland_changeable' => true));
		$this->change_element_type('label', 'hidden', array('userland_changeable' => true));

		$this->change_element_type('kind', 'radio', array(
			'options' => [
				"subtitles" => "<strong>Subtitles</strong><br> Text that provides a translation of spoken words and text on screen. For viewers who don't know the video's language.",
				"captions" => "<strong>Captions</strong><br> Text that describes all audible content, including speech, sound effects, music, etc. For viewers with hearing impairment or with sound off.",
			],
		));
		$this->add_required('kind');

		$this->set_display_name('content', 'Track Content');
		$this->change_element_type('content', 'hidden', array('userland_changeable' => true));

		$this->add_element('upload', 'upload');
		$this->set_display_name('upload', 'Select WebVTT Track File');
		$this->add_comments('upload', form_comment("Alternatively, <a href='javascript:void(0);' class='toggle-manual-content'>add/edit Track content</a> manually instead"));

		$this->set_display_name('kind', 'Track Type');

		$this->set_display_name('lang', 'Language');
		$this->change_element_type('lang', 'select_no_sort', array(
			'options' => $this->languages,
			'display_name' => 'Track Language')
		);
	}

	function init_head_items()
	{
		$this->head_items->add_javascript(JQUERY_URL, true);
		$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH . 'content_managers/media_captions.js');
		$this->head_items->add_stylesheet(REASON_HTTP_BASE_PATH . 'css/media_captions.css');
		parent::init_head_items();
	}

	function init($externally_set_up = false)
	{
		if (!$this->inited) {
			$this->inited = true;
			// Need to preserve the webvtt content as is
			// https://w3c.github.io/webvtt/#introduction-other-features
			array_push($this->_no_tidy, "content");
			$this->strip_tags_from_user_input = false;
		}
		parent::init();
	}

}

?>