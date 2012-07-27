<?php
/**
* This file contains the DorianSHCampConfirmation class
* @author Matt Ryan <mryan@acs.carleton.edu>
*/

/**
* Include necessary stuff
*/
include_once('reason_header.php');
include_once(WEB_PATH.'stock/allBandPFclass.php');

/**
* GiftConfirmation, Matt Ryan, 29 March 2005
*
* This class wraps up the logic needed to get the confirmation
* text out of the gift database in a (fairly) secure way.  It relies
* on getting both a reference number via set_ref_number and a hash
* string via set_hash before checking for validation and then
* producing the confirmation text.  The validation check and the
* return of the confirmation text are separated so that things that
* use this have the option of either using the default error
* message provided or delivering a more contextual error message
* to the user.  Validation is determined thusly: if the hash value
* given equals the md5 hash of the confirmation text, then we are
* good to go.
*
* Sample code
* <code>
* $foo = new GiftConfirmation;
* $foo->set_ref_number('V54A60348496');
* $foo->set_hash('43f289f51426f06b2f6e8f5f550280b7'); // good
* //$foo->set_hash('43f289f51426f06b2f6e8f5f550280be'); // bad
* if($foo->validates())
* {
* 	echo $foo->get_confirmation_text();
* }
* else
* {
* 	echo $foo->get_error_message();
* }
* </code>
*/
class AllBandConfirmation
{
	/**
	* Reference number from PayFlowPro
	* This is, essentially, the key used to retrieve the confirmation text from the databaase
	* @var string
	*/
	var $reference_number;

	/**
	* A hash value that the GiftConfirmation object will use to validate the request for the confirmation text
	* It will be compared against an md5 of the confirmation text if the reference number exists in the db
	* @var string
	*/
	var $hash;

	/**
	* Internal record of the object's validation status
	* Set and retrieved in validates().
	* @var bool
	* @access private
	*/
	var $_validates = false;

	/**
	* Internal record of the confirmation text
	* This is only set if validation has occurred
	* @var string
	* @access private
	*/
	var $_confirm_text = '';

	/**
	* Internal record of whether validation has been checked
	* Used by validates() to see if has to check validation or if it can just return $_validates
	* @var bool
	* @access private
	*/
	var $_validation_has_run = false;

	/**
	* Default error message
	* Available to scripts that use this class by calling get_error_message()
	* @var string
	*/
	var $error_message = 'We\'re sorry.  Your gift was successfully processed, but there appears to have been an error generating your confirmation information.  Please contact the Alumni Office for assistance.';

	/**
		* Setter for PayFlowPro transaction reference number
		* @param string $ref_num
	*/
	function set_ref_number($ref_num)
	{
		$this->reference_number = $ref_num;
	}
	/**
		* Setter for the hash value to be used in validation
		* @param string $hash
	*/
	function set_hash($hash)
	{
		$this->hash = $hash;
	}
	/**
		* Checks validation against results from DB query
		* Compares the hash provided in set_hash() with an md5 of the confirmation text
		* get_confirmation_text() checks with this function before returning confirmation text.
		* This function does the actual instantiation of a gift class, which does the direct DB query
		* @return bool true value indicates that things are OK; false indicates that things are not OK
	*/
	function validates()
	{
		if(!$this->_validation_has_run)
		{
			if(!empty($this->reference_number))
			{
				$npf = new allBandPF;
				$confirm_text = $npf->get_confirmation_text($this->reference_number);
				if(!empty($confirm_text))
				{
					if($this->hash == $this->make_hash($confirm_text))
					{
						$this->_confirm_text = $confirm_text;
						$this->_confirm_text .= '<p>A copy of this confirmation has been sent to your email address.</p>';
						$this->_validation_has_run = true;
						$this->_validates = true;
					}
					else
					{
						unset($confirm_text);
						unset($npf);
						$this->_validation_has_run = true;
						$this->_validates = false;
					}

				}
			}
		}
		return $this->_validates;
	}
	/**
		* Checks validation against results from DB query
		* Returns a string containing the confirmation text if validation occurs
		* Returns false if validation does not occur
		* Note that the confirmation text is likely to be in UTF-8
		* @return mixed
	*/
	function get_confirmation_text()
	{
		if($this->validates())
		{
			return $this->_confirm_text;
		}
		else
		{
			return false;
		}

	}
	/**
		* Allows instantiating scripts to use a default error message provided by this class
		* @return string
	*/
	function get_error_message()
	{
		echo $this->error_message;
	}
	function make_hash( $string )
	{
		return md5($string);
	}

}

/* if( __FILE__ == $_SERVER[ 'SCRIPT_FILENAME' ] )
{
	$foo = new GiftConfirmation;
	$foo->set_ref_number('V54A60348496');
	$foo->set_hash('43f289f51426f06b2f6e8f5f550280b7'); // good
	//$foo->set_hash('43f289f51426f06b2f6e8f5f550280be'); // bad
	if($foo->validates())
	{
		echo $foo->get_confirmation_text();
	}
	else
	{
		echo $foo->get_error_message();
	}
} */
?>