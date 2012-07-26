CSGOLD/Reason integration tools from Carleton College
Mark Heiman (mheiman@carleton.edu)

onecard.php
	General purpose class for reading and writing data from the CSGOLD system.  No Reason
	dependencies, so you can use it in other contexts.

onecard-image.php
	Simple script for returning a cardholder image via a web request.

oracle_db.php
	A lightweight Oracle connection class used by onecard.php.  If you have a different
	preferred way of connecting to Oracle from PHP, you'll need to make some changes to
	how onecard.php interacts with the database.
	
onecard_dashboard.php
	A Reason module for viewing card status and transaction history (goes in minisite_templates/modules)

onecard_credit.php
onecard_credit
	A Reason module and supporting files for putting funds into a card SVC account.  Depends on 
	PayPal PayflowPro transaction processing (not included)