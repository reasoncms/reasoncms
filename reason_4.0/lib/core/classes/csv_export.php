<?php
/**
 * PHP export
 * @package reason
 * @subpackage classes
 */
 
 include_once('reason_header.php');
 reason_include_once('function_libraries/asset_functions.php');
 reason_include_once('function_libraries/images.php');
 reason_include_once('function_libraries/util.php');
 
/**
 * Class for handling standard Reason exports
 *
 * Takes an array of entities and outputs a csv file
 *
 *
 * @author Ibrahim Rabbani
 *
 * Sample code:
 * $es = new entity_selector();
 * // ... some rules go in here ...
 * $entities = $es->run_one();
 * 
 * $export = new reason_csv_export();
 * $export->show_all_columns(true);
 *
 * foreach($export->get_headers($type_id,$site_id) as $header)
 *     header($header);
 * echo $export->get_csv($entities,$type_id,$site_id);
 */

class reason_csv_export
{
    protected $show_all_columns = false;
    
    function show_all_columns($value = NULL)
    {
    	if(!isset($value))
    	{
    		return $this->show_all_columns;
    	}
    	$this->show_all_columns = (boolean) $value;
    }

    /**
     * Get an array of three arrays that can be written as a CSV
     * @todo the geopoint field returns a strings of NULL that is not recognized as empty by empty() 
     * and is not trimmed by trim(). It ends up being in the output even though it is an empty string. 
     * Fixing this would make the output more consistent. The problem is in the lines
     * $column_value = trim($v);
     *  if(!empty($column_value)) {
     * @access private
     * @param array $entities
     * @return array [$headings,$relationships,$data]
     */ 

    protected function get_csv_data($entities,$type_id,$site_id = NULL) {
        $output = array();
        $headings = array('id','type','site');
        $relationships = array();
        $allowable_relns = get_allowable_relationships_for_type($type_id);        
        if ($this->show_all_columns === true) {
            array_push($headings,'unique_name');
        }
        /**
         * Get Data For Entities
         */ 
        foreach($entities as $e)
        {
        	if(!empty($site_id))
        	{
        		$e->set_env('restrict_site',true);
        		$e->set_env('site',$site_id);
        	}
            $type = new entity($e->get_value('type'));
            $site = $e->get_owner();
            $id = $e->id();
            $output[$id] = array();
            $output[$id]['id'] = $id;
            $output[$id]['type'] = $type->get_value('unique_name');
            $output[$id]['site'] = $site->get_value('unique_name');
            $output_str = '';
            if($e->get_value('unique_name')) {
                if($this->show_all_columns == false) {
                    $string = trim($e->get_value('unique_name'));
                    if(!empty($string))
                        $output_str .= $e->get_value('unique_name');
                        if (array_search('unique_name', $headings) === false)
                            array_push($headings, 'unique_name');
                } else { 
                    $output_str .= $e->get_value('unique_name');
                }
            }

            foreach($e->get_values() as $k=>$v) {
                if ($this->show_all_columns === false) {
                    $column_value = trim($v);
                    if(!empty($column_value)) {
                        if ($k != 'user_password_hash') {
                            if (array_search($k, $headings) === false)
                                array_push($headings, $k);
                            $output[$id][$k] = $v;
                        }
                    }
                }
                else {
                    if ($k != 'user_password_hash') {
                        if (array_search($k, $headings) === false)
                            array_push($headings, $k);
                        $output[$id][$k] = $v;                    
                    }
                }
            }

            foreach($allowable_relns as $relation) {
                if($relation['type'] == 'association') {
                    $key = $relation['name'];
                    $relns = array();
                    // check to see if reln is left or right
                    if ($relation['relationship_a'] == $type_id) {
                        $key .= ':left';
                        $output[$id][$key] = array();
                        $relns = $e->get_left_relationship($relation['name']);
                        foreach($relns as $entity) {
                            $entity_name = str_replace("'","\'",$entity->get_value('name'));
                            $data = "'".$entity_name."'".','.$entity->id();
                            array_push($output[$id][$key],$data);
                        }
                    } else {
                        $key .= ':right';
                        $output[$id][$key] = array();
                        $relns = $e->get_right_relationship($relation['name']);
                        foreach($relns as $entity) {
                            $entity_name = str_replace("'","\'",$entity->get_value('name'));
                            $data = "'".$entity_name."'".','.$entity->id();
                            array_push($output[$id][$key],$data);
                        }
                    }
                    if (array_search($key,$relationships) === false) {
                        if ($this->show_all_columns === false) {
                            if(!empty($relns))
                                array_push($relationships,$key);
                        }
                        else {
                            array_push($relationships,$key);
                        }
                    }
                }
            }  
        }
        $headings = array_merge($headings,$relationships);
        return array($headings,$relationships,$output);
    }

    /**
     * Get a CSV representation for a set of Reason entities
     * @access private
     * @param array $headings 
     * @param array $relationships
     * @param array $output
     * @return emit CSV File using php headers
     */ 
    protected function generate_csv($headings,$relationships,$output,$type_id,$site_id = NULL)
    {
        ob_start();
        $outputFile = fopen("php://output", 'w');
        fputs( $outputFile, "\xEF\xBB\xBF" );
        fputcsv($outputFile, $headings);
        foreach (array_keys($output) as $entity) {
            $string_to_write = '';
            foreach ($headings as $heading) {
                if (isset($output[$entity][$heading]) && $output[$entity][$heading] != ''){
                    if (is_array($output[$entity][$heading])) {
                        foreach ($output[$entity][$heading] as $reln) {
                            $string_to_write .= $reln.';';
                        }
                        $string_to_write.= '|';
                    }
                    else
                        $string_to_write .= $output[$entity][$heading].'|';
                }
                else
                    $string_to_write .= ''.'|';
            }
            fputcsv($outputFile,explode('|',$string_to_write));
        }
        fclose($outputFile);
        return ob_get_clean();
    }
    
    public function get_csv($entities,$type_id,$site_id = NULL)
    {
        $data = $this->get_csv_data($entities,$type_id,$site_id);
        return $this->generate_csv($data[0],$data[1],$data[2],$type_id,$site_id);
    }
    
    public function get_headers($type_id,$site_id = NULL)
    {
    	$ret = array();
    	$site_name = '';
        if(!empty($site_id))
        {
        	$site = new entity($site_id);
        	$site_name = str_replace(' ','_',strip_tags($site->get_value('name')));
        }
        $entity = new entity($type_id);
        $entity_name = str_replace(' ','_',$entity->get_value('name'));
        $filename = 'csv_export-'.$site_name.'-'.$entity_name.'.csv';
        $ret[] = 'Content-Disposition: attachment; filename='.$filename;
        $ret[] = 'Content-Encoding: UTF-8';
        $ret[] = "content-type:application/csv;charset=UTF-8";
        return $ret;
    }
}