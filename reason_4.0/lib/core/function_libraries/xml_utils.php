<?php
/**
 * @package reason
 * @subpackage function_libraries
 */
/**
 * Convert a string of xml to an array
 * @author Nate White
 * @param string $xml
 * @return mixed array if success; else null
 */
function xmlstring2array($xml)
{
   //$ReElements = '/<(\w+)\s*([^\/>]*)\s*(?:\/>|>(.*)<\/\s*\\1\s*>)/s';
   $ReElements = '/<(\w+)\s*([^\/>]*)\s*(?:\/>|>(.*?)<(\/\s*\1\s*)>)/s';
   $ReAttributes = '/(\w+)=(?:"|\')([^"\']*)(:?"|\')/';
   preg_match_all ($ReElements, $xml, $elements);
   foreach ($elements[1] as $ie => $xx) {
   $xmlary[$ie]["name"] = $elements[1][$ie];
     if ( $attributes = trim($elements[2][$ie])) {
         preg_match_all ($ReAttributes, $attributes, $att);
         foreach ($att[1] as $ia => $xx)
           // all the attributes for current element are added here
           $xmlary[$ie]["attributes"][$att[1][$ia]] = $att[2][$ia];
     } // if $attributes
    
     // get text if it's combined with sub elements
   $cdend = strpos($elements[3][$ie],"<");
   if ($cdend > 0) {
           $xmlary[$ie]["text"] = substr($elements[3][$ie],0,$cdend -1);
       } // if cdend
      
     if (preg_match ($ReElements, $elements[3][$ie]))       
         $xmlary[$ie]["elements"] = xmlstring2array ($elements[3][$ie]);
     //else if ($elements[3][$ie]){
      //   $xmlary[$ie]["text"] = $elements[3][$ie];
      //   }
      else if (isset($elements[3][$ie])){
      $xmlary[$ie]["text"] = $elements[3][$ie];
      }
   }
   if(!empty($xmlary))
   {
   	return $xmlary;
	}
}
?>