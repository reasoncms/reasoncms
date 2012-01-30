<?php
/** Event Invite email class
 *
 * @package carl_util
 * @subpackage event_invite
 */
 
/** 
 * Allows you to send email to Zimbra (or elsewhere) with an attached ical event in such a way that the event will be offered
 * to the recipient for inclusion in their personal calendar.
 *
 * Usage:
 *
 *   $invite = new EventInvite($recipient, $sender, $subject, $message, $ical_data, $debug_flag)
 *   $invite->send(True); // True = send debug information to error_log 
 *
 *   $invite->setRecipient($new_recipient);
 *   $invite->send(); // send a copy to someone else
 * 
 * Functions:
 *
 *   // Constructor
 *   EventInvite ( $recipient = null , $sender = "wsg@carleton.edu" , $subject = "Incoming Event", $message = null , $ical_data = null )
 *   
 *   // send the message
 *   send ( $debug = FALSE )
 *   
 *   // get/set the recipient  
 *   getRecipient ( )
 *   setRecipient ( $new_recipient )
 *   
 *   // get/set the sender
 *   getSender ( )
 *   setSender ( $new_sender )
 *   
 *   // get/set the message subject
 *   getSubject ( )
 *   setSubject ( $new_subject )
 *   
 *   // get/set the message body
 *   getMessage ( )
 *   setMessage ( $new_message )
 *   
 *   // get/set the ical data -- see EventInviteExample.php for the appropriate set of ical fields
 *   getICalData ( )
 *   setICalData ( $new_ical_data )
 *   
 *   // used internally for building the message headers  
 *   mainHeader ( )
 *   messageHeader ( )
 *   icalHeader ( )
 *   getBoundary ( )
 *   setBoundary ( )
 *   boundaryKey ($length=10)
 *
 * @author Matt Bockol
 * @version .001
 * @date 10.3.2007
 */
class EventInvite
{
    var $recipient  = null ; 
    var $sender     = null ; 
    var $subject    = null ; 
    var $ical_data  = null ; 
    var $message    = null ; 
    var $boundary   = null ; 
    var $debug      = FALSE ; 

    function EventInvite ( $recipient = null , $sender = "wsg@carleton.edu" , $subject = "Incoming Event", $message = null , $ical_data = null )
    {
        $this->recipient = $recipient ; 
        $this->sender = $sender ; 
        $this->subject = $subject ; 
        $this->message = $message ; 
        $this->ical_data = $ical_data ; 
        $this->debug = $debug ; 
    }

    function send ( $debug = FALSE )
    {
        if(is_null($this->recipient)){
            if($this->debug){ error_log("EventInvite: recipient null."); }
            return FALSE ; 
            }

        if(is_null($this->sender)){
            if($this->debug){ error_log("EventInvite: sender null."); }
            return FALSE ; 
            }

        if(is_null($this->ical_data)){
            if($this->debug){ error_log("EventInvite: ical_data null."); }
            return FALSE ; 
            }
        
        if(is_null($this->message)){
            if($this->debug){ error_log("EventInvite: message null."); }
            }

        $fullheaders = $this->mainHeader() . $this->messageHeader() . $this->icalHeader() . "--" . $this->getBoundary() . "--\n";
        mail($this->recipient, $this->subject, "" , $fullheaders);
    }


    function getRecipient ( )
    {
        return $this->recipient ; 
    }

    function setRecipient ( $new_recipient )
    {
        if(isset($new_recipient))
        {
            $this->recipient = $new_recipient ; 
        }

        return $this->recipient ; 
    }


    function getSender ( )
    {
        return $this->sender ; 
    }

    function setSender ( $new_sender )
    {
        if(isset($new_sender))
        {
            $this->sender = $new_sender ; 
        }

        return $this->sender ; 
    }


    function getSubject ( )
    {
        return $this->subject ; 
    }

    function setSubject ( $new_subject )
    {
        if(isset($new_subject))
        {
            $this->subject = $new_subject ; 
        }

        return $this->subject ; 
    }


    function getMessage ( )
    {
        return $this->message ; 
    }

    function setMessage ( $new_message )
    {
        if(isset($new_message))
        {
            $this->message = $new_message ; 
        }

        return $this->message ; 
    }


    function getICalData ( )
    {
        return $this->ical_data ; 
    }

    function setICalData ( $new_ical_data )
    {
        if(isset($new_ical_data))
        {
            $this->recipient = $new_ical_data ; 
        }

        return $this->ical_data ; 
    }


    function mainHeader ( )
    {
        $main_header = ""; 
        $main_header .= "From: " . $this->sender . "\n"; 
        $main_header .= "MIME-Version: 1.0\n"; 
        $main_header .= "Content-Type: multipart/alternative;\n    boundary=\"" . $this->getBoundary() . "\"\n"; 
        $main_header .= "X-Originating-IP: [137.22.1.42]\n\n"; 
        return $main_header ; 
    }


    function messageHeader ( )
    {
        // with no message, we return an empty header
        if(!isset( $this->message )){ return ; }

        $message_header = ""; 
        $message_header .= "--" . $this->getBoundary() . "\n"; 
        $message_header .= "Content-Type: text/plain; charset=utf-8\n"; 
        $message_header .= "Content-Transfer-Encoding: 7bit\n\n"; 
        $message_header .= $this->message . "\n"; 

        return $message_header ; 
    }


    function icalHeader ( )
    {
        // with no ical data, we return an empty header
        if(!isset( $this->ical_data )){ return ; }
        $ical_header = ""; 
        $ical_header .= "--" . $this->getBoundary() . "\n";  
        $ical_header .= "Content-Type: text/calendar; charset=utf-8; method=REQUEST; name=meeting.ics\n"; 
        $ical_header .= "Content-Transfer-Encoding: 7bit\n\n"; 
        $ical_header .= $this->ical_data ; 

        return $ical_header ; 
    }


    function getBoundary ( )
    {
        if(!isset($this->boundary)){ $this->setBoundary(); } 
        return $this->boundary ; 
    }


    function setBoundary ( )
    {
        $this->boundary = "----=_Part_" . $this->boundaryKey(7) . "_" . $this->boundaryKey(10) . "." . $this->boundaryKey(13) ; 
        return $this->boundary ; 
    }


    // generate a random boundary key
    function boundaryKey ($length=10)
    {
        $key = ""; 

        while($length > 0)
        {
            $key .= rand(0,9);
            $length-- ; 
        }

        return $key ; 
    }
}
?>
