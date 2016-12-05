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

	var $fields_to_remove = array();
	var $field_order = array('name', 'label', 'kind', 'lang', 'content', 'upload', 'content_manual', 'unique_name');
	var $inited = false;
	var $languages = array(
		// A ISO-639-1 language code list  
		"" => "",
		"English" => "en",
		"Spanish" => "es",
		"Abkhazian" => "ab",
		"Afar" => "aa",
		"Afrikaans" => "af",
		"Albanian" => "sq",
		"Amharic" => "am",
		"Arabic" => "ar",
		"Armenian" => "hy",
		"Assamese" => "as",
		"Aymara" => "ay",
		"Azerbaijani" => "az",
		"Bashkir" => "ba",
		"Basque" => "eu",
		"Bengali" => "bn",
		"Bhutani" => "dz",
		"Bihari" => "bh",
		"Bislama" => "bi",
		"Breton" => "br",
		"Bulgarian" => "bg",
		"Burmese" => "my",
		"Byelorussian" => "be",
		"Cambodian" => "km",
		"Catalan" => "ca",
		"Chinese" => "zh",
		"Corsican" => "co",
		"Croatian" => "hr",
		"Czech" => "cs",
		"Danish" => "da",
		"Dutch" => "nl",
		"English" => "en",
		"Esperanto" => "eo",
		"Estonian" => "et",
		"Faeroese" => "fo",
		"Fiji" => "fj",
		"Finnish" => "fi",
		"French" => "fr",
		"Frisian" => "fy",
		"Galician" => "gl",
		"Georgian" => "ka",
		"German" => "de",
		"Greek" => "el",
		"Greenlandic" => "kl",
		"Guarani" => "gn",
		"Gujarati" => "gu",
		"Hausa" => "ha",
		"Hebrew" => "he",
		"Hindi" => "hi",
		"Hungarian" => "hu",
		"Icelandic" => "is",
		"Indonesian" => "id",
		"Interlingua" => "ia",
		"Interlingue" => "ie",
		"Inupiak" => "ik",
		"Inuktitut (Eskimo)" => "iu",
		"Irish" => "ga",
		"Italian" => "it",
		"Japanese" => "ja",
		"Javanese" => "jw",
		"Kannada" => "kn",
		"Kashmiri" => "ks",
		"Kazakh" => "kk",
		"Kinyarwanda" => "rw",
		"Kirghiz" => "ky",
		"Kirundi" => "rn",
		"Korean" => "ko",
		"Kurdish" => "ku",
		"Laothian" => "lo",
		"Latin" => "la",
		"Latvian, Lettish" => "lv",
		"Lingala" => "ln",
		"Lithuanian" => "lt",
		"Macedonian" => "mk",
		"Malagasy" => "mg",
		"Malay" => "ms",
		"Malayalam" => "ml",
		"Maltese" => "mt",
		"Maori" => "mi",
		"Marathi" => "mr",
		"Moldavian" => "mo",
		"Mongolian" => "mn",
		"Nauru" => "na",
		"Nepali" => "ne",
		"Norwegian" => "no",
		"Occitan" => "oc",
		"Oriya" => "or",
		"Oromo" => "om",
		"Pashto, Pushto" => "ps",
		"Persian" => "fa",
		"Polish" => "pl",
		"Portuguese" => "pt",
		"Punjabi" => "pa",
		"Quechua" => "qu",
		"Rhaeto-Romance" => "rm",
		"Romanian" => "ro",
		"Russian" => "ru",
		"Samoan" => "sm",
		"Sangro" => "sg",
		"Sanskrit" => "sa",
		"Scots Gaelic" => "gd",
		"Serbian" => "sr",
		"Serbo-Croatian" => "sh",
		"Sesotho" => "st",
		"Setswana" => "tn",
		"Shona" => "sn",
		"Sindhi" => "sd",
		"Singhalese" => "si",
		"Siswati" => "ss",
		"Slovak" => "sk",
		"Slovenian" => "sl",
		"Somali" => "so",
		"Spanish" => "es",
		"Sudanese" => "su",
		"Swahili" => "sw",
		"Swedish" => "sv",
		"Tagalog" => "tl",
		"Tajik" => "tg",
		"Tamil" => "ta",
		"Tatar" => "tt",
		"Tegulu" => "te",
		"Thai" => "th",
		"Tibetan" => "bo",
		"Tigrinya" => "ti",
		"Tonga" => "to",
		"Tsonga" => "ts",
		"Turkish" => "tr",
		"Turkmen" => "tk",
		"Twi" => "tw",
		"Uigur" => "ug",
		"Ukrainian" => "uk",
		"Urdu" => "ur",
		"Uzbek" => "uz",
		"Vietnamese" => "vi",
		"Volapuk" => "vo",
		"Welch" => "cy",
		"Wolof" => "wo",
		"Xhosa" => "xh",
		"Yiddish" => "yi",
		"Yoruba" => "yo",
		"Zhuang" => "za",
		"Zulu" => "zu",
	);

	function on_every_time()
	{
		$this->add_element('content_manual', 'textarea', array('display_name' => 'Edit Track Content'));

		$this->set_order($this->field_order);
	}

	function pre_error_check_actions()
	{
		$manual_edits = trim($this->get_value('content_manual'));
		if ($manual_edits) {
			$this->set_value('content', $manual_edits);
		}
	}

	function alter_data()
	{
		// Apply a few frequent defaults for new entities
		if ($this->_is_first_time() && !$this->has_errors() && $this->is_new_entity() && !$this->get_value('name')) {
			$this->set_value('label', 'English');
			$this->set_value('kind', 'captions');
			$this->set_value('lang', 'en');
		}

		$this->set_display_name('name', 'Internal Name');

		$this->set_display_name('label', 'Track Display Name');

		$this->set_display_name('content', 'Track Content');
		$this->change_element_type('content', 'hidden', array('userland_changeable' => true));

		$this->add_element('upload', 'upload');
		$this->set_display_name('upload', 'Upload Track File');
		$this->add_comments('upload', form_comment("Alternatively, <a href='javascript:void(0);' class='toggle-manual-content'>add/edit Track content</a> manually instead"));

		$this->set_display_name('kind', 'Track Type');

		$this->set_display_name('lang', 'Language Code');
		$this->change_element_type('lang', 'select_no_sort', array(
			'options' => array_flip($this->languages),
			'display_name' => 'Track Language Code')
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
			array_push($this->_no_tidy, "content");
		}
		parent::init();
	}

}

?>