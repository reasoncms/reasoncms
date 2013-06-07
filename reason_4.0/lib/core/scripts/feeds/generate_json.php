<?php
/**
 * include dependencies
 * TODO: require authentication.
 */
$reason_session = false;
include_once( 'reason_header.php' );
reason_include_once( 'classes/entity_selector.php' );
reason_include_once('function_libraries/image_tools.php');

$start_time = get_microtime();
/**
 * ReasonJSON is just a stub that you can extend for some JSON-
 * handling goodness.
 **/
class ReasonJSON
{
	var $es;
	var $raw_items;
	var $items;
	var $json;
	var $num;
	var $start;

	/**
	 * The constructor instantiates an entity and adds some
	 * defaults.
	 * 
	 * 	
	 **/
	function __construct($type, $site)
	{
		$this->es = new entity_selector($site);
		$this->es->add_type($type);
		$this->es->set_num(15);
		$this->es->set_order('last_modified DESC');
	}

	function _get_items() {
		$this->raw_items = $this->es->run_one();
	}
	function _build_json() {
		$this->json = json_encode($this->items);
	}
	function set_num($num) 
	{
		$this->num = $num;
	}
	function set_start($start)
	{
		$this->start = $start;
	}
}

class ReasonImagesJSON extends ReasonJSON
{
	function __construct($site, $type)
	{
		parent::__construct($site, $type);

		if( !empty($_REQUEST['q']) )
		{
			$this->es->add_relation('(entity.name LIKE "%'.addslashes($_REQUEST['q']) . '%"' .
						      ' OR meta.description LIKE "%' . addslashes($_REQUEST['q']) . '%"'.
							  ' OR meta.keywords LIKE "%' . addslashes($_REQUEST['q']) . '%"'.
							  ' OR chunk.content LIKE "%' . addslashes($_REQUEST['q']) . '%"'.
							  ')');
		}
	}
	function _transform_items()
	{
		if (empty($this->raw_items))
			$this->items = Array("null");
    else foreach ($this->raw_items as $k => $v)
		{
			$newArray = array();
			$newArray['id'] = $v->get_value('id');
			$newArray['name'] = $v->get_value('name');
			$newArray['description'] = $v->get_value('description');
			$newArray['pubDate'] = $v->get_value('creation_date');
			$newArray['link'] = $this->make_image_link($newArray['id'], 'standard');
			$newArray['thumbnail'] = $this->make_image_link($newArray['id'], 'thumbnail');
			$this->items[] = $newArray;
		}
	}
	function make_image_link($id, $size=null)
	{
		$filename = reason_get_image_filename($id, $size);
		return 'http://' . REASON_HOST.WEB_PHOTOSTOCK. $filename;
	}
	function run()
	{
			$this->es->set_num( $this->num );
			$this->es->set_start( $this->start);
			$this->es->set_order( 'entity.last_modified DESC, dated.datetime DESC, entity.name ASC' );
			$this->_get_items();
			$this->_transform_items();
			$this->_build_json();
			return $this->json;
	}
}

if (isset($_GET['type_id']) && isset($_GET['site_id'])) {
		$type_id = turn_into_int($_GET['type_id']);
		$site_id = turn_into_int($_GET['site_id']);
		if ($type_id == id_of('image')) {
				$reasonImagesJson = new ReasonImagesJSON($type_id, $site_id);
				// Add the edits for all of the extra fields you might give.
				$num = !empty($_REQUEST['num']) ? turn_into_int($_REQUEST['num']) : '500';
				$start = !empty($_REQUEST['start']) ? turn_into_int($_REQUEST['start']) : '0';
				$reasonImagesJson->set_num($num);
				$reasonImagesJson->set_start($start);
				print($reasonImagesJson->run());
		}
} else
{
	http_response_code(400);
	echo json_encode(array("error" => 400));
}

reason_log_page_generation_time( round( 1000 * (get_microtime() - $start_time) ) );


?>
