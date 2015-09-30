<?php
/**
 * Term-based sorting functions
 * 
 * This code is not fully generalized -- it assumes a quarter/trimester system
 * rather than the more common semester system.
 *
 * @author Mark Heiman, adapted by Jason Oswald
 *
 * Jason notes: "added summer "term" in, just in case. made it functionally equivalent
 * a fall term and so we don't lose the 'adjacency' of spring and fall."
 *
 * @package reason
 * @subpackage function_libraries
 * @todo Generalize to handle other term systems
 * @todo Fully document these functions
 */

/**
 * @param array $termlist
 * @return array
 */
function term_range( $termlist )
{
	if( !is_array( $termlist ) )
		$termlist = array( $termlist );
    usort($termlist, "termCmp");
    return termRange($termlist);
}

/**
 * @param string $a
 * @param string $b
 * @return integer
 */
function termCmp( $a, $b ) 
{
    // sort an array of terms in 00/TT format
    list($aYear,$aTerm) = explode("/",$a);
    list($bYear,$bTerm) = explode("/",$b);
    $term["FA"] = 3;
    $term["SU"] = 3;
    $term["SP"] = 2;
    $term["WI"] = 1;
    if ($aYear == $bYear) 
    { 
       $result = ($term[$aTerm] < $term[$bTerm]) ? -1 : 1; 
    }
    else
    { 
        $result = ($aYear < $bYear) ? -1 : 1; 
    }
    return $result;
}

/**
 * @param array $termlist
 * @return string
 */
function termRange($termlist) 
{
    // Take a list of terms in 00/TT format and turn them into
    // a list of ranges of terms for display.
    $termName["FA"] = "Fall";
    $termName["WI"] = "Winter";
    $termName["SP"] = "Spring";
    $termName["SU"] = "Summer";
    
    $term["FA"] = 3;
    $term["SU"] = 3;
    $term["SP"] = 2;
    $term["WI"] = 1;
    $start[] = $termlist[0];
    $currEnd = $termlist[0];

    for ($i=0; $i<(sizeof($termlist)-1); $i++) 
    {
        list($aYear,$aTerm) = explode("/",$termlist[$i]);
        list($bYear,$bTerm) = explode("/",$termlist[$i+1]);
        // if the year is the same, see if the terms are sequential
        if ($aYear == $bYear) 
        {
            if (($term[$aTerm] + 1) == $term[$bTerm]) 
            {
                $currEnd = $termlist[$i+1];
            }
            else 
            {
                $end[] = $termlist[$i];
                $start[] = $termlist[$i+1];
                $currEnd = $termlist[$i+1];
            }
        // if the years aren't the same, see if they're sequential
        } 
        elseif ($aYear + 1 == $bYear) 
        {
            if ($term[$aTerm] - $term[$bTerm] == 2) 
            {
                $currEnd = $termlist[$i+1];
            } 
            else 
            {
                $end[] = $termlist[$i];
                $start[] = $termlist[$i+1];
                $currEnd = $termlist[$i+1];
            }
        // if the years aren't the same, start a new range
        } 
        else 
        {
            $end[] = $termlist[$i];
            $start[] = $termlist[$i+1];
            $currEnd = $termlist[$i+1];
        }
    }
    $end[] = $currEnd;
    $range = array();
    for ($i=0; $i<sizeof($start); $i++) 
    {
        
        // convert to Term Year format for display
        list($lyear,$lterm) = explode("/", $start[$i]);
        $start[$i] = $termName[$lterm]." 20".$lyear;
        list($lyear,$lterm) = explode("/", $end[$i]);
        $end[$i] = $termName[$lterm]." 20".$lyear;
        $range[] .= ($start[$i] == $end[$i]) ? $start[$i] : 
        $start[$i]." through ".$end[$i];
    }
    if( is_array($range) ) 
    {
        $range_str = join(", ",$range);
    }
    return $range_str;
}
?>
