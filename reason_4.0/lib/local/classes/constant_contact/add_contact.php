<?php
require_once 'src/Ctct/autoload.php';

use Ctct\ConstantContact;
use Ctct\Components\Contacts\Contact;
use Ctct\Components\Contacts\ContactList;
use Ctct\Components\Contacts\EmailAddress;
use Ctct\Exceptions\CtctException;


//function add_contact($key = "d4kgr6pat5vrgyzew6vq2n8e", $token = "6ad6b53a-4d71-4495-8c6e-c01c33590864", $email, $list)
function add_contact($token, $listname, $email)
// given an access token for a given constant contact account, sign up a given email to the listname specified
// called from minisite_templates/modules/form/views/thor/constant_contact_add_email.php
{
	$key = "d4kgr6pat5vrgyzew6vq2n8e";
	//$token = "6ad6b53a-4d71-4495-8c6e-c01c33590864";
	$cc = new ConstantContact($key);
	
	// check to see if a contact with the email addess already exists in the account
	$response = $cc->getContactByEmail($token, $email);
	// create a new contact if one does not exist
	if (empty($response->results))
	{
		$contact = new Contact();
		$contact->addEmail($email);
	}
	else	
	{
		$contact = $response->results[0];
	}
	
	// attempt to fetch lists in the account, catching any exceptions and printing the errors to screen
	try
	{
		$lists = $cc->getLists($token);
		foreach ($lists as $list)
		{
			if ($list->name == $listname)
			{
				$contact->addList($list);
				$returnContact = empty($response->results) ? $cc->addContact($token, $contact) : $cc->updateContact($token, $contact);
				break;
			}
		}
	}
	catch (CtctException $ex)
	{
		foreach ($ex->getErrors() as $error)
		{
			print_r($error);
		}
	}
}
?>