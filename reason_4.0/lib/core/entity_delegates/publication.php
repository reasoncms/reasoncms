<?php

reason_include_once( 'entity_delegates/abstract.php' );
reason_include_once( 'helpers/publication_helper.php' );

$GLOBALS['entity_delegates']['entity_delegates/image.php'] = 'publicationDelegate';

/**
 * @todo resolve issue -- use of __call in a delegate kind of breaks the scheme
 * @todo figure out a better way to do this
 */
class publicationDelegate extends entityDelegate {
	protected $helper;
	protected function get_helper()
	{
		if(!isset($this->helper))
		{
			$this->helper = new PublicationHelper($this->entity->id());
			$this->helper->set_values($this->entity->get_values());
		}
		return $this->helper;
	}
	public function __call($name, $arguments)
	{
		$helper = $this->get_helper();
		if(method_exists($name))
			return call_user_func_array(array($delegate,$name),$arguments);
		trigger_error('Method does not exist on PublicationHelper class');
	}
}