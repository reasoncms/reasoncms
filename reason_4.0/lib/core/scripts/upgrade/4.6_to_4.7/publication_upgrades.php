<?php
/**
 * Carleton's 2015 News portal redesign spawned a whole bunch of Publication enhancements that can
 * be folded into the general system.
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
// reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('scripts/upgrade/reason_db_helper.php');

$GLOBALS['_reason_upgraders']['4.6_to_4.7']['publication_upgrades'] = 'ReasonUpgrader_47_PublicationUpgradeChanges';

class ReasonUpgrader_47_PublicationUpgradeChanges implements reasonUpgraderInterface
{
	protected $_user_id;
	public function user_id( $user_id = NULL) {
		if(!empty($user_id))
			return $this->_user_id = $user_id;
		else
			return $this->_user_id;
	}


	// create the EmbedHandler type
	private $EMBED_HANDLER_NICENAME = "News / Post Embed Handler";
	private $EMBED_HANDLER_TABLE_NAME = "news_post_embed_handler";
	private $EMBED_HANDLER_TYPE_NAME = "news_post_embed_handler_type";
	private $EMBED_HANDLER_CONTENT_MANAGER = "news_embed_handler.php";
	private $EMBED_HANDLER_DETAILS;
	private $EMBED_HANDLER_ENTITY_FIELDS = Array(
		'class_name' => array('db_type' => 'varchar(128)'),
		'path' => array('db_type' => 'varchar(256)'),
	);

	// create the post to embed relationship
	private $POST_TO_EMBED_DETAILS = array(
		'description'=>'News/Post to Embed Handler',
		'directionality'=>'bidirectional',
		'connections'=>'one_to_many', // a post can only have one embed handler associated with it, but a single handler can be associated with many posts
		'required'=>'no',
		'is_sortable'=>'no',
		'display_name'=>'Associate Custom Embed Handler',
		'display_name_reverse_direction'=>'Assign News/Posts',
		'description_reverse_direction'=>'News/Posts with this Embed Handler'
	);
	
	// create the post to org relationship
	private $POST_TO_ORG_DETAILS = array(
		'description'=>'News/Post to Organization Handler',
		'directionality'=>'bidirectional',
		'connections'=>'one_to_many', // a post can only have one organization associated with it, but a single organization can be associated with many posts
		'required'=>'no',
		'is_sortable'=>'no',
		'display_name'=>'Associate URL Organization',
		'display_name_reverse_direction'=>'Set as URL Organization for News/Posts',
		'description_reverse_direction'=>'News/Posts with this URL Organization'
	);
	
	private $rsnDbHelper;

	public function __construct() {
		$this->EMBED_HANDLER_DETAILS = Array( 'new'=>0, 'unique_name'=> $this->EMBED_HANDLER_TYPE_NAME, 'custom_content_handler'=>$this->EMBED_HANDLER_CONTENT_MANAGER, 'plural_name'=>$this->EMBED_HANDLER_NICENAME . "s");

		$this->rsnDbHelper = new ReasonDbHelper();
		$this->rsnDbHelper->setUsername(reason_check_authentication());
	}

	/**
	 * Get the title of the upgrader
	 * @return string
	 */
	public function title()
	{
		return "Various upgrades related to Publication/NewsPost entities.";
	}

	/**
	 * Get a description of what this upgrade script will do
	 * @return string HTML description
	 */
	public function description()
	{
		return "<p>This upgrade:<ul>" .
			"<li>creates an 'Embed Handler' entity type, and a NewsPost->EmbedHandler relationship, used in giving a custom look and feel on a per-story basis to a Publication Post" .
			"<li>Updated News/Post entities to allow them to serve as external links" .
			"</ul></p>";
	}

	private function echoYesNo($test) {
		$fontColor = $test ? "green" : "red";
		$wording = $test ? "YES" : "NO";
		return "<font color='$fontColor'>$wording</font>";
	}
	
	/**
	 * Do a test run of the upgrader
	 * @return string HTML report
	 */
	public function test()
	{
		return "<p>" .
				"Embed Handler type '" . $this->EMBED_HANDLER_TYPE_NAME . "' already exists? " . $this->echoYesNo($this->rsnDbHelper->typeAlreadyExists($this->EMBED_HANDLER_TYPE_NAME)) . "<br>" .
				"News/Post type '" . $this->EMBED_HANDLER_TYPE_NAME . "' has column 'linkpost_url' " . $this->echoYesNo($this->rsnDbHelper->columnExistsOnTable("newstype", "linkpost_url")) . "<br>" .
			"</p>";
	}

    /**
     * Run the upgrader
     * @return string HTML report
     */
	public function run()
	{
		echo "Creating '" . $this->EMBED_HANDLER_TYPE_NAME . "':<br>";
		if ($this->rsnDbHelper->typeAlreadyExists($this->EMBED_HANDLER_TYPE_NAME)) {
			echo $this->EMBED_HANDLER_TYPE_NAME . " entity already exists; skipping...<br>";
		} else {
			$embedHandlerTypeId = $this->rsnDbHelper->createTypeHelper($this->EMBED_HANDLER_NICENAME, $this->EMBED_HANDLER_TABLE_NAME, $this->EMBED_HANDLER_TYPE_NAME, $this->EMBED_HANDLER_ENTITY_FIELDS, $this->EMBED_HANDLER_DETAILS);
			// $embedHandlerTypeId = 1209573;
			echo "Created type " . $this->EMBED_HANDLER_TYPE_NAME . ", with id " . $embedHandlerTypeId . "<br>";
			$postToEmbedRelId = $this->rsnDbHelper->createAllowableRelationshipHelper(id_of("news"), $embedHandlerTypeId, "news_post_to_embed_handler", $this->POST_TO_EMBED_DETAILS);
			echo "Created news/post <-> embed handler relationsip with id " . $postToEmbedRelId . "<br>";
		}

		echo "<hr>";

		if ($this->rsnDbHelper->columnExistsOnTable("newstype", "linkpost_url")) {
			echo "newstype already has a 'linkpost_url' column; skipping...<br>";
		} else {
			$this->rsnDbHelper->addFieldsToEntity("newstype", array("linkpost_url" => "varchar(255) DEFAULT ''"));

			echo "added linkpost_url field to newstype<br>";
			if (id_of("organization_type", true, false) != 0) {
				$postToOrganizationRelId = $this->rsnDbHelper->createAllowableRelationshipHelper(id_of("news"), id_of("organization_type"), "news_post_to_url_organization", $this->POST_TO_ORG_DETAILS);
				echo "added allowable relationship between news/post and organization<br>";
			} else {
				echo "organization_type doesn't exist; not adding relationship between org and news/post (this is NOT an error)<br>";
			}
		}
	}


}
?>
