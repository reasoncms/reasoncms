<?

/*
 * Copyright (c) 2003 Starnetsys, LLC.  All rights reserved.
 * Redistribution of source must retain this copyright notice.
 *
 * Starnetsys, LLC (http://starnetsys.com) specializes in
 * website design and software consulting.
 */

define ("NODE_TYPE_START",0);
define ("NODE_TYPE_ELEMENT",1);
define ("NODE_TYPE_ENDELEMENT",2);
define ("NODE_TYPE_TEXT",3);
define ("NODE_TYPE_COMMENT",4);
define ("NODE_TYPE_DONE",5);

/**
 * Class HtmlParser.
 * To use, create an instance of the class passing
 * HTML text. Then invoke parse() until it's false.
 * When parse() returns true, $iNodeType, $iNodeName
 * $iNodeValue and $iNodeAttributes are updated.
 */
class HtmlParser {

    /**
     * Field iNodeType.
     * May be one of the NODE_TYPE_* constants above.
     */
    var $iNodeType;

    /**
     * Field iNodeName.
     * For elements, it's the name of the element.
     */
    var $iNodeName = "";

    /**
     * Field iNodeValue.
     * For text nodes, it's the text.
     */
    var $iNodeValue = "";

    /**
     * Field iNodeAttributes.
     * A string-indexed array containing attribute values
     * of the current node. Indexes are always lowercase.
     */
    var $iNodeAttributes;

    // The following fields should be 
    // considered private:

    var $iHtmlText;
    var $iHtmlTextLength;
    var $iHtmlTextIndex = 0;


    /**
     * Constructor.
     * Constructs an HtmlParser instance with
     * the HTML text given.
     */
    function HtmlParser ($aHtmlText) {
        $this->iHtmlText = $aHtmlText;
        $this->iHtmlTextLength = strlen($aHtmlText);
    }

    /**
     * Method parse.
     * Parses the next node. Returns false only if
     * the end of the HTML text has been reached.
     * Updates values of iNode* fields.
     */
    function parse() {
        $text = $this->skipToElement();
        if ($text != "") {
            $this->iNodeType = NODE_TYPE_TEXT;
            $this->iNodeName = "Text";
            $this->iNodeValue = $text;
            return true;
        }
        return $this->readTag();
    }

    function clearAttributes() {
        $iNodeAttributes = array();
    }

    function readTag() {
        if ($this->currentChar() != "<") {
            $this->iNodeType = NODE_TYPE_DONE;
            return false;
        }
        $this->skipInTag ("<");
        $this->clearAttributes();
        $name = $this->skipToBlanksInTag();
        $pos = strpos($name, "/");
        if ($pos === 0) {
            $this->iNodeType = NODE_TYPE_ENDELEMENT;
            $this->iNodeName = substr ($name, 1);
            $this->iNodeValue = "";
        } 
        else {
            if (!$this->isValidTagIdentifier ($name)) {
                $comment = false;
                if ($name == "!--") {
                    $rest = $this->skipToStringInTag ("-->");    
                    if ($rest != "") {
                        $this->iNodeType = NODE_TYPE_COMMENT;
                        $this->iNodeName = "Comment";
                        $this->iNodeValue = "<" . $name . $rest;
                        $comment = true;
                    }
                }
                if (!$comment) {
                    $this->iNodeType = NODE_TYPE_TEXT;
                    $this->iNodeName = "Text";
                    $this->iNodeValue = "<" . $name;
                }
                return true;
            }
            else {
                $this->iNodeType = NODE_TYPE_ELEMENT;
                $this->iNodeValue = "";
                $nameLength = strlen($name);
                if ($nameLength > 0 && substr($name, $nameLength - 1, 1) == "/") {                 $this->iNodeName = substr($name, 0, $nameLength - 1);
                }
                else {
                    $this->iNodeName = $name;
                }
            } 
        }
        while ($this->skipBlanksInTag()) {
            $attrName = $this->skipToBlanksOrEqualsInTag();
            if ($attrName != "") {
                $this->skipBlanksInTag();
                if ($this->currentChar() == "=") {
                    $this->skipEqualsInTag();
                    $this->skipBlanksInTag();
                    $value = $this->readValueInTag();
                    $this->iNodeAttributes[strtolower($attrName)] = $value;
                }
                else {
                    $this->iNodeAttributes[strtolower($attrName)] = "";
                }
            }
        }
        $this->skipEndOfTag();
        return true;            
    }

    function isValidTagIdentifier ($name) {
        return ereg ("[A-Za-z0-9]+", $name);
    }
    
    function skipBlanksInTag() {
        return "" != ($this->skipInTag (array (" ", "\t", "\r", "\n" )));
    }

    function skipToBlanksOrEqualsInTag() {
        return $this->skipToInTag (array (" ", "\t", "\r", "\n", "=" ));
    }

    function skipToBlanksInTag() {
        return $this->skipToInTag (array (" ", "\t", "\r", "\n" ));
    }

    function skipEqualsInTag() {
        return $this->skipInTag (array ( "=" ));
    }

    function readValueInTag() {
        $ch = $this->currentChar();
        $value = "";
        if ($ch == "\"") {
            $this->skipInTag (array ( "\"" ));
            $value = $this->skipToInTag (array ( "\"" ));
            $this->skipInTag (array ( "\"" ));
        }
        else if ($ch == "\'") {
            $this->skipInTag (array ( "\'" ));
            $value = $this->skipToInTag (array ( "\'" ));
            $this->skipInTag (array ( "\'" ));
        }                
        else {
            $value = $this->skipToBlanksInTag();
        }
        return $value;
    }

    function currentChar() {
        if ($this->iHtmlTextIndex >= $this->iHtmlTextLength) {
            return -1;
        }
        return $this->iHtmlText{$this->iHtmlTextIndex};
    }

    function moveNext() {
        if ($this->iHtmlTextIndex < $this->iHtmlTextLength) {
            $this->iHtmlTextIndex++;
            return true;
        }
        else {
            return false;
        }
    }

    function skipEndOfTag() {
        $sb = "";
        if (($ch = $this->currentChar()) !== -1) {
            $match = ($ch == ">");
            if (!$match) {
                return $sb;
            }
            $sb .= $ch;
            $this->moveNext();
        }
        return $sb;
    }

    function skipInTag ($chars) {
        $sb = "";
        while (($ch = $this->currentChar()) !== -1) {
            if ($ch == ">") {
                return $sb;
            } else {
                $match = false;
                for ($idx = 0; $idx < count($chars); $idx++) {
                    if ($ch == $chars[$idx]) {
                        $match = true;
                        break;
                    }
                }
                if (!$match) {
                    return $sb;
                }
                $sb .= $ch;
                $this->moveNext();
            }
        }
        return $sb;
    }

    function skipToInTag ($chars) {
        $sb = "";
        while (($ch = $this->currentChar()) !== -1) {
            $match = $ch == ">";
            if (!$match) {
                for ($idx = 0; $idx < count($chars); $idx++) {
                    if ($ch == $chars[$idx]) {
                        $match = true;
                        break;
                    }
                }
            }
            if ($match) {
                return $sb;
            }
            $sb .= $ch;
            $this->moveNext();
        }
        return $sb;
    }

    function skipToElement() {
        $sb = "";
        while (($ch = $this->currentChar()) !== -1) {
            if ($ch == "<") {
                return $sb;
            }
            $sb .= $ch;
            $this->moveNext();
        }
        return $sb;             
    }

    /**
     * Returns text between current position and $needle,
     * inclusive, or "" if not found. The current index is moved to a point
     * after the location of $needle, or not moved at all
     * if nothing is found.
     */
    function skipToStringInTag ($needle) {
        $pos = strpos ($this->iHtmlText, $needle, $this->iHtmlTextIndex);
        if ($pos === false) {
            return "";
        }
        $top = $pos + strlen($needle);
        $retvalue = substr ($this->iHtmlText, $this->iHtmlTextIndex, $top - $this->iHtmlTextIndex);
        $this->iHtmlTextIndex = $top;
        return $retvalue;
    }
}

?>
