<?php
/**
* Interface for displayer chrome wrapper.
*/ 
interface DisplayerChromeWrapperInterface
{	
	/**
	* Constructs a displayer chrome for the given chrome type.
	* @param $chrome_type string
	*
	* Class for each possible chrome type should be implemented in the
	* displayer_chrome directory for each integration library, including default.
	* Examples for $chrome_type: 'default', 'av', 'content_manager'
	*/
	public function get_chrome_from_type($chrome_type);
	
}
?>