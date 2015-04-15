<?php
/*
    +----------------------------------------------------------------------+
    | Copyright (c) 2000 J.A.Greant                                        |
    | (See end of file for usage notes and licensing information.)         |
    +----------------------------------------------------------------------+
*/

class credit_card
{
    function clean_no ($cc_no)
    {
        // Remove non-numeric characters from $cc_no
        return ereg_replace ('[^0-9]+', '', $cc_no);
    }

    function identify ($cc_no)
    {
         $cc_no = credit_card::clean_no ($cc_no);

        // Get card type based on prefix and length of card number
        if (ereg ('^4(.{12}|.{15})$', $cc_no))
            return 'Visa';
        if (ereg ('^5[1-5].{14}$', $cc_no))
            return 'Mastercard';
        if (ereg ('^3[47].{13}$', $cc_no))
            return 'American Express';
        if (ereg ('^3(0[0-5].{11}|[68].{12})$', $cc_no))
            return 'Diners Club/Carte Blanche';
        if (ereg ('^6011.{12}$', $cc_no))
            return 'Discover Card';
        if (ereg ('^(3.{15}|(2131|1800).{11})$', $cc_no))
            return 'JCB';
        if (ereg ('^2(014|149).{11})$', $cc_no))
            return 'enRoute';

        return 'unknown';
    }

    function validate ($cc_no)
    {
        // Reverse and clean the number
        $cc_no = strrev (credit_card::clean_no ($cc_no));
        
        // VALIDATION ALGORITHM
        // Loop through the number one digit at a time
        // Double the value of every second digit (starting from the right)
        // Concatenate the new values with the unaffected digits
		$digits = '';
        for ($ndx = 0; $ndx < strlen ($cc_no); ++$ndx)
            $digits .= ($ndx % 2) ? $cc_no[$ndx] * 2 : $cc_no[$ndx];
        
        // Add all of the single digits together
		$sum = 0;
        for ($ndx = 0; $ndx < strlen ($digits); ++$ndx)
            $sum += $digits[$ndx];

        // Valid card numbers will be transformed into a multiple of 10
        return ($sum % 10) ? FALSE : TRUE;
    }

    function check ($cc_no)
    {
        $valid = credit_card::validate ($cc_no);
        $type  = credit_card::identify ($cc_no);
        return array ($valid, $type, 'valid' => $valid, 'type' => $type);
    }
}
/*
    +----------------------------------------------------------------------+
    | FILE NAME: credit_card.pkg                                           |
    +----------------------------------------------------------------------+
    | Author: J.A.Greant                                                   |
    | Email : zak@nucleus.com                                              |
    | Date  : 2000/11/23                                                   |
    +----------------------------------------------------------------------+
    | The credit_card class provides methods for cleaning, validating and  |
    | identifying the type of credit card number. The validation algorithm |
    | and identification procedures are based on information found at:     |
    |          http://www.beachnet.com/~hstiles/cardtype.html              |
    |                                                                      |
    | credit_card::clean_no() method                                       |
    | ------------------------------                                       |
    | Strips all non-numeric characters from the passed value and returns  |
    | an integer.  This method is called by the other methods in the       |
    | credit_card class.                                                   |
    |                                                                      |
    | USAGE EXAMPLE:                                                       |
    | $cc_no ="5454 5454 5454 5454"; // Has spaces for readability         |
    | $cleaned_cc_no = credit_card::clean_no ($cc_no);                     |
    | print $cleaned_cc_no; // Displays 5454545454545454                   |
    |                                                                      |
    | credit_card::identify() method                                       |
    | ------------------------------                                       |
    | Finds the type of credit card (Mastercard, Visa, etc...) based on    |
    | the length and format of the credit card number.  The method can     |
    | identify American Express, Diners Club/Carte Blanche, Discover,      |
    | enRoute, JCB, Mastercard and Visa cards.                             |
    |                                                                      |
    | USAGE EXAMPLE:                                                       |
    | $cc_no ="5454 5454 5454 5454";                                       |
    | print credit_card::identify ($cc_no); // Returns "Mastercard"        |
    |                                                                      |
    | credit_card::validate() method                                       |
    | ------------------------------                                       |
    | Validate a credit card number using the LUHN formula (mod 10).       |
    | Note that many other kinds of card and account numbers are based on  |
    | the LUHN algorith - including Canadian Social Insurance Numbers.     |
    |                                                                      |
    | USAGE EXAMPLE:                                                       |
    | $cc_no ="5454 5454 5454 5454";                                       |
    | print credit_card::validate ($cc_no); // Returns TRUE                |
    |                                                                      |
    | credit_card::check() method                                          |
    | ---------------------------                                          |
    | The check() method validates and identifies a credit card, returning |
    | an array. Indexes 0 and 'valid' will contain TRUE if the card number |
    | is valid (FALSE if it isn't), while indexes 1 and 'type' will        |
    | the type of card (if known). The presence of the numeric keys allows |
    | the method to be used with the list() function.                      |
    |                                                                      |
    | USAGE EXAMPLE:                                                       |
    | $cc_no ="4111 1111 1111 1111";                                       |
    | list ($valid, $type) = credit_card::check ($cc_no);                  |
    | print $valid; // Displays 1 (TRUE)                                   |
    | print $type;  // Displays "Visa"                                     |
    +----------------------------------------------------------------------+

    +----------------------------------------------------------------------+
    | CVS LOG INFO                                                         |
    +----------------------------------------------------------------------+
      $Log: credit_card_validator.pkg,v $
      Revision 1.2  2000/11/24 07:02:15  zak
      Added top copyright notice

      Revision 1.1  2000/11/24 06:58:42  zak
      Initial commit of credit card class to repository.
      Cleaned and modified code.
      Also rewrote significant parts of code so that I could change to the
      more friendly BSD-style license.


    +----------------------------------------------------------------------+
    | Copyright (c) 2000 J.A.Greant (jag@nucleus.com)                      |
    | All rights reserved.                                                 |
    +----------------------------------------------------------------------+
    | Redistribution and use in source and binary forms, with or without   |
    | modification, is permitted provided that the following conditions    |
    | are met:                                                             |
    +----------------------------------------------------------------------+
    | Redistributions of source code must retain the above copyright       |
    | notice, this list of conditions and the following disclaimer.        |
    |                                                                      |
    | Redistributions in binary form must reproduce the above copyright    |
    | notice, this list of conditions and the following disclaimer in the  |
    | documentation and/or other materials provided with the distribution. |
    |                                                                      |
    | Neither the name of the author nor the names of any contributors to  |
    | this software may be used to endorse or promote products derived     |
    | from this software without specific prior written permission.        |
    |                                                                      |
    | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
    | ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT  |
    | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS    |
    | FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE       |
    | AUTHOR OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,           |
    | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
    | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;     |
    | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER     |
    | CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT   |
    | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN    |
    | ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE      |
    | POSSIBILITY OF SUCH DAMAGE.                                          |
    +----------------------------------------------------------------------+
*/
?> 