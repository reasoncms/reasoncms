<?php

/**
 * Type library for offering country, state, province, and language choices.
 * @package disco
 * @subpackage plasmature
 */

require_once PLASMATURE_TYPES_INC."options.php";

/**
 * The plasmature class for a drop-down of languages.
 * @package disco
 * @subpackage plasmature
 */
class languageType extends select_no_sortType
{
 	var $type = 'language';
	var $type_valid_args = array( 'exclude_these_languages' );
	/**
	 * Array of languages that should not be included in the {@link options}.
	 * @var array
	 */
	var $exclude_these_languages = array();
	/**
	 *  Adds the default languages to the {@link options} array.
	 */
	function load_options( $args = array() )
	{
		$languages = array(
							'eng' => 'English',
							'alb' => 'Albanian',
							'ara' => 'Arabic',
							'arm' => 'Armenian',
							'asm' => 'Assamese',
							'aze' => 'Azerbaijani',
							'bel' => 'Belarusian',
							'ben' => 'Bengali',
							'bul' => 'Bulgarian',
							'cat' => 'Catalan/Valencian',
							'chi' => 'Chinese',
							'scr' => 'Croatian',
							'cze' => 'Czech',
							'dan' => 'Danish',
							'dut' => 'Dutch/Flemish',
							'est' => 'Estonian',
							'fin' => 'Finnish',
							'fre' => 'French',
							'geo' => 'Georgian',
							'ger' => 'German',
							'gre' => 'Greek',
							'guj' => 'Gujarati',
							'heb' => 'Hebrew',
							'hin' => 'Hindi',
							'hun' => 'Hungarian',
							'ice' => 'Icelandic',
							'ita' => 'Italian',
							'jpn' => 'Japanese',
							'jav' => 'Javanese',
							'kor' => 'Korean',
							'lav' => 'Latvian',
							'lit' => 'Lithuanian',
							'mac' => 'Macedonian',
							'may' => 'Malay',
							'mal' => 'Malayalam',
							'mar' => 'Marathi',
							'mol' => 'Moldavian',
							'nor' => 'Norwegian',
							'per' => 'Persian',
							'pol' => 'Polish',
							'por' => 'Portuguese',
							'rum' => 'Romanian',
							'rus' => 'Russian',
							'scc' => 'Serbian',
							'slo' => 'Slovak',
							'slv' => 'Slovenian',
							'spa' => 'Spanish/Castilian',
							'swe' => 'Swedish',
							'tgl' => 'Tagalog',
							'tam' => 'Tamil',
							'tat' => 'Tatar',
							'tel' => 'Telugu',
							'tha' => 'Thai',
							'tur' => 'Turkish',
							'ukr' => 'Ukrainian',
							'urd' => 'Urdu',
							'vie' => 'Vietnamese',
							'xxx' => 'Other',
		);
		foreach( $languages as $key => $val )
		{
			if(!in_array($val, $this->exclude_these_languages))
				$this->options[ $key ] = $val;
		}
	}
}
/**
 * The plasmature class for a drop-down of U.S. states.
 *
 * To include Canadian provinces, use {@link state_provinceType}.
 *
 * To include military APO/FPO codes, pass the include_military_codes argument as true
 *
 * @package disco
 * @subpackage plasmature
 */
class stateType extends selectType
{
 	var $type = 'state';
	var $type_valid_args = array('use_not_in_usa_option','include_military_codes');
	var $sort_options = false;
	/**
	 * Adds a "Not in the US" option to the state type.
	 * The default value is false. It can also be set to "top" if the "Not in US" option should appear at the top.
	 * All non-empty values other than "top" will append the option to the end of the list.
	 * @var mixed
	 */
	var $use_not_in_usa_option = false;
	/**
	 * Adds the US military state codes to the list of options
	 * The default value is false. A true value will add the military state codes after Wyoming.
	 * @var boolean
	 */
	var $include_military_codes = false;
	/**
	 *  Populates the {@link options} array.
	 */
	function load_options( $args = array())
	{
		$this->load_states();
		//if use_not_in_usa is set to 'top', put it at the top of the select
		if($this->use_not_in_usa_option === 'top')
		{
			$temp = array('XX' => 'Not in USA');
			$temp[] = '--';
			$this->options = array_merge($temp, $this->options);
		}
		//for any other true value, stick it at the bottom of the select
		elseif($this->use_not_in_usa_option)
		{
			$this->options[] = '--';
			$this->options['XX'] = 'Not in USA';
		}
	}
	/**
	 *  Adds the U.S. states to the {@link options} array.
	 *  Helper function to {@link load_options()}.
	 */
	//load_states is outside of load_options so that state_province can use load_states, too
	function load_states()
	{
		$states = array(
			'AL' => 'Alabama',
			'AK' => 'Alaska',
			'AZ' => 'Arizona',
			'AR' => 'Arkansas',
			'CA' => 'California',
			'CO' => 'Colorado',
			'CT' => 'Connecticut',
			'DE' => 'Delaware',
			'DC' => 'District of Columbia',
			'FL' => 'Florida',
			'GA' => 'Georgia',
			'HI' => 'Hawaii',
			'ID' => 'Idaho',
			'IL' => 'Illinois',
			'IN' => 'Indiana',
			'IA' => 'Iowa',
			'KS' => 'Kansas',
			'KY' => 'Kentucky',
			'LA' => 'Louisiana',
			'ME' => 'Maine',
			'MD' => 'Maryland',
			'MA' => 'Massachusetts',
			'MI' => 'Michigan',
			'MN' => 'Minnesota',
			'MS' => 'Mississippi',
			'MO' => 'Missouri',
			'MT' => 'Montana',
			'NE' => 'Nebraska',
			'NV' => 'Nevada',
			'NH' => 'New Hampshire',
			'NJ' => 'New Jersey',
			'NM' => 'New Mexico',
			'NY' => 'New York',
			'NC' => 'North Carolina',
			'ND' => 'North Dakota',
			'OH' => 'Ohio',
			'OK' => 'Oklahoma',
			'OR' => 'Oregon',
			'PA' => 'Pennsylvania',
			'RI' => 'Rhode Island',
			'SC' => 'South Carolina',
			'SD' => 'South Dakota',
			'TN' => 'Tennessee',
			'TX' => 'Texas',
			'UT' => 'Utah',
			'VT' => 'Vermont',
			'VA' => 'Virginia',
			'WA' => 'Washington',
			'WV' => 'West Virginia',
			'WI' => 'Wisconsin',
			'WY' => 'Wyoming',
		);
		if($this->include_military_codes)
		{
			$states[] = '--';
			$states['AA'] = 'AA (Military APO/FPO)';
			$states['AE'] = 'AE (Military APO/FPO)';
			$states['AP'] = 'AP (Military APO/FPO)';
		}
		foreach( $states as $key => $val )
			$this->options[ $key ] = $val;
	}
}
/**
 * The plasmature element for a  drop-down of U.S. states and Canadian provinces.
 * To display only U.S. states, use {@link stateType}.
 * @package disco
 * @subpackage plasmature
 */
class state_provinceType extends stateType
{
 	var $type = 'state_province';
	var $use_not_in_usa_option = true;
	var $sort_options = false;
	function load_options( $args = array())
	{
		$this->load_states();
		$this->options[] = '--';
		$this->load_provinces();
		if($this->use_not_in_usa_option === 'top')
		{
			$temp = array('XX' => 'Not in USA/Canada');
			$temp[] = '--';
			$this->options = array_merge($temp, $this->options);
		}
		elseif($this->use_not_in_usa_option)
		{
			$this->options[] = '--';
			$this->options['XX'] = 'Not in USA/Canada';
		}
	}
	/**
	 *  Adds the Canadian states to the {@link options} array.
	 *  Helper function to {@link load_options()}.
	 */
	function load_provinces()
	{
		$provinces = array(
			'AB' => 'Alberta',
			'BC' => 'British Columbia',
			'MB' => 'Manitoba',
			'NB' => 'New Brunswick',
			'NL' => 'Newfoundland and Labrador',
			'NT' => 'Northwest Territories',
			'NS' => 'Nova Scotia',
			'NU' => 'Nunavut',
			'ON' => 'Ontario',
			'PE' => 'Prince Edward Island',
			'QC' => 'Quebec',
			'SK' => 'Saskatchewan',
			'YT' => 'Yukon',
		);
		foreach( $provinces as $key => $val )
			$this->options[ $key ] = $val;
	}
}
/**
 * The plasmature element for  a drop-down of countries.
 * @package disco
 * @subpackage plasmature
 *
 *@todo Add "exclude these countries" functionality -- like in the {@link languageType}
 */
class countryType extends selectType
{
 	var $type = 'country';
	var $sort_options = false;	//false so that we can have USA at the top.
	/**
	 * Populates the {@link options} array with a default list of countries.
	 */
	function load_options( $args = array() )
	{
		$countries = array(
			'USA' => 'United States of America',
			'AFG' => 'Afghanistan',
			'ALB' => 'Albania',
			'DZA' => 'Algeria',
			'ASM' => 'American Samoa',
			'AND' => 'Andorra',
			'AGO' => 'Angola',
			'AIA' => 'Anguilla',
			'ATA' => 'Antarctica',
			'ATG' => 'Antigua and Barbuda',
			'ARG' => 'Argentina',
			'ARM' => 'Armenia',
			'ABW' => 'Aruba',
			'AUS' => 'Australia',
			'AUT' => 'Austria',
			'AZE' => 'Azerbaijan',
			'BHS' => 'Bahamas',
			'BHR' => 'Bahrain',
			'BGD' => 'Bangladesh',
			'BRB' => 'Barbados',
			'BLR' => 'Belarus',
			'BEL' => 'Belgium',
			'BLZ' => 'Belize',
			'BEN' => 'Benin',
			'BMU' => 'Bermuda',
			'BTN' => 'Bhutan',
			'BOL' => 'Bolivia',
			'BIH' => 'Bosnia and Herzegovina',
			'BWA' => 'Botswana',
			'BVT' => 'Bouvet Island',
			'BRA' => 'Brazil',
			'IOT' => 'British Indian Ocean',
			'VGB' => 'British Virgin Islands',
			'BRN' => 'Brunei Darussalam',
			'BGR' => 'Bulgaria',
			'BFA' => 'Burkina Faso',
			'BDI' => 'Burundi',
			'KHM' => 'Cambodia',
			'CMR' => 'Cameroon',
			'CAN' => 'Canada',
			'CPV' => 'Cape Verde',
			'CYM' => 'Cayman Islands',
			'CAF' => 'Central African Republic',
			'TCD' => 'Chad',
			'CHL' => 'Chile',
			'CHN' => 'China',
			'CXR' => 'Christmas Island',
			'CCK' => 'Cocos Islands',
			'COL' => 'Colombia',
			'COM' => 'Comoros',
			'COD' => 'Congo, Dem. Republic of',
			'COG' => 'Congo',
			'COK' => 'Cook Islands',
			'CRI' => 'Costa Rica',
			'CIV' => 'Cote d\'Ivoire',
			'CUB' => 'Cuba',
			'CYP' => 'Cyprus',
			'CZE' => 'Czech Republic',
			'DNK' => 'Denmark',
			'DJI' => 'Djibouti',
			'DMA' => 'Dominica',
			'DOM' => 'Dominican Republic',
			'ECU' => 'Ecuador',
			'EGY' => 'Egypt',
			'SLV' => 'El Salvador',
			'GNQ' => 'Equatorial Guinea',
			'ERI' => 'Eritrea',
			'EST' => 'Estonia',
			'ETH' => 'Ethiopia',
			'FRO' => 'Faroe Islands',
			'FLK' => 'Falkland Islands',
			'FJI' => 'Fiji the Fiji Islands',
			'FIN' => 'Finland',
			'FRA' => 'France',
			'GUF' => 'French Guiana',
			'PYF' => 'French Polynesia',
			'ATF' => 'French Southern Territories',
			'GAB' => 'Gabon',
			'GMB' => 'Gambia the',
			'GEO' => 'Georgia',
			'DEU' => 'Germany',
			'GHA' => 'Ghana',
			'GIB' => 'Gibraltar',
			'GRC' => 'Greece',
			'GRL' => 'Greenland',
			'GRD' => 'Grenada',
			'GLP' => 'Guadeloupe',
			'GUM' => 'Guam',
			'GTM' => 'Guatemala',
			'GIN' => 'Guinea',
			'GNB' => 'Guinea-Bissau',
			'GUY' => 'Guyana',
			'HTI' => 'Haiti',
			'HMD' => 'Heard & McDonald Islands',
			'VAT' => 'Holy See (Vatican)',
			'HND' => 'Honduras',
			'HKG' => 'Hong Kong',
			'HRV' => 'Hrvatska (Croatia)',
			'HUN' => 'Hungary',
			'ISL' => 'Iceland',
			'IND' => 'India',
			'IDN' => 'Indonesia',
			'IRN' => 'Iran',
			'IRQ' => 'Iraq',
			'IRL' => 'Ireland',
			'ISR' => 'Israel',
			'ITA' => 'Italy',
			'JAM' => 'Jamaica',
			'JPN' => 'Japan',
			'JOR' => 'Jordan',
			'KAZ' => 'Kazakhstan',
			'KEN' => 'Kenya',
			'KIR' => 'Kiribati',
			'PRK' => 'Korea, North',
			'KOR' => 'Korea, South',
			'KWT' => 'Kuwait',
			'KGZ' => 'Kyrgyz Republic',
			'LAO' => 'Laos',
			'LVA' => 'Latvia',
			'LBN' => 'Lebanon',
			'LSO' => 'Lesotho',
			'LBR' => 'Liberia',
			'LBY' => 'Libya',
			'LIE' => 'Liechtenstein',
			'LTU' => 'Lithuania',
			'LUX' => 'Luxembourg',
			'MAC' => 'Macau',
			'MKD' => 'Macedonia',
			'MDG' => 'Madagascar',
			'MWI' => 'Malawi',
			'MYS' => 'Malaysia',
			'MDV' => 'Maldives',
			'MLI' => 'Mali',
			'MLT' => 'Malta',
			'MHL' => 'Marshall Islands',
			'MTQ' => 'Martinique',
			'MRT' => 'Mauritania',
			'MUS' => 'Mauritius',
			'MYT' => 'Mayotte',
			'MEX' => 'Mexico',
			'FSM' => 'Micronesia',
			'MDA' => 'Moldova',
			'MCO' => 'Monaco',
			'MNG' => 'Mongolia',
			'MSR' => 'Montserrat',
			'MAR' => 'Morocco',
			'MOZ' => 'Mozambique',
			'MMR' => 'Myanmar',
			'NAM' => 'Namibia',
			'NRU' => 'Nauru',
			'NPL' => 'Nepal',
			'ANT' => 'Netherlands Antilles',
			'NLD' => 'Netherlands the',
			'NCL' => 'New Caledonia',
			'NZL' => 'New Zealand',
			'NIC' => 'Nicaragua',
			'NER' => 'Niger the',
			'NGA' => 'Nigeria',
			'NIU' => 'Niue',
			'NFK' => 'Norfolk Island',
			'MNP' => 'Northern Mariana Islands',
			'NOR' => 'Norway',
			'OMN' => 'Oman',
			'PAK' => 'Pakistan',
			'PLW' => 'Palau',
			'PSE' => 'Palestinian Territory',
			'PAN' => 'Panama',
			'PNG' => 'Papua New Guinea',
			'PRY' => 'Paraguay',
			'PER' => 'Peru',
			'PHL' => 'Philippines the',
			'PCN' => 'Pitcairn Island',
			'POL' => 'Poland',
			'PRT' => 'Portugal',
			'PRI' => 'Puerto Rico',
			'QAT' => 'Qatar',
			'REU' => 'Reunion',
			'ROU' => 'Romania',
			'RUS' => 'Russian Federation',
			'RWA' => 'Rwanda',
			'SHN' => 'St. Helena',
			'KNA' => 'St. Kitts and Nevis',
			'LCA' => 'St. Lucia',
			'SPM' => 'St. Pierre and Miquelon',
			'VCT' => 'St. Vincent & the Grenadines',
			'WSM' => 'Samoa',
			'SMR' => 'San Marino',
			'STP' => 'Sao Tome and Principe',
			'SAU' => 'Saudi Arabia',
			'SEN' => 'Senegal',
			'SCG' => 'Serbia and Montenegro',
			'SYC' => 'Seychelles',
			'SLE' => 'Sierra Leone',
			'SGP' => 'Singapore',
			'SVK' => 'Slovakia',
			'SVN' => 'Slovenia',
			'SLB' => 'Solomon Islands',
			'SOM' => 'Somalia',
			'ZAF' => 'South Africa',
			'SGS' => 'South Georgia',
			'ESP' => 'Spain',
			'LKA' => 'Sri Lanka',
			'SDN' => 'Sudan',
			'SUR' => 'Suriname',
			'SJM' => 'Svalbard',
			'SWZ' => 'Swaziland',
			'SWE' => 'Sweden',
			'CHE' => 'Switzerland',
			'SYR' => 'Syrian Arab Republic',
			'TWN' => 'Taiwan',
			'TJK' => 'Tajikistan',
			'TZA' => 'Tanzania',
			'THA' => 'Thailand',
			'TLS' => 'Timor-Leste',
			'TGO' => 'Togo',
			'TKL' => 'Tokelau',
			'TON' => 'Tonga',
			'TTO' => 'Trinidad and Tobago',
			'TUN' => 'Tunisia',
			'TUR' => 'Turkey',
			'TKM' => 'Turkmenistan',
			'TCA' => 'Turks and Caicos',
			'TUV' => 'Tuvalu',
			'UGA' => 'Uganda',
			'UKR' => 'Ukraine',
			'ARE' => 'United Arab Emirates',
			'GBR' => 'United Kingdom & N. Ireland',
			'UMI' => 'US Minor Outlying Islands',
			'VIR' => 'US Virgin Islands',
			'URY' => 'Uruguay',
			'UZB' => 'Uzbekistan',
			'VUT' => 'Vanuatu',
			'VEN' => 'Venezuela',
			'VNM' => 'Vietnam',
			'WLF' => 'Wallis and Futuna Islands',
			'ESH' => 'Western Sahara',
			'YEM' => 'Yemen',
			'ZMB' => 'Zambia',
			'ZWE' => 'Zimbabwe',
		);
		foreach( $countries as $key => $val )
			$this->options[ $key ] = $val;
	}
}
