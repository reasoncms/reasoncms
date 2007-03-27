<?php

include_once( 'paths.php' );
require_once( DISCO_INC.'disco.php' );
require_once( DISCO_INC.'plasmature/plasmature.php' );
require_once( THOR_INC.'boxes_thor.php' );

class DiscoThor extends Disco
{
	var $box_class = 'BoxThor';
	var $finished = false;
	
	function run()
	{
		parent::run();
	}
	
	function process()
	{
		$this->finished = true;
	}
	
	function process_email($values = '')
	{	
		$values = (is_array($values)) ? $values : $this->get_values_array();
		include_once(TYR_INC.'tyr.php');
		$tyr = new Tyr($values['messages'], $values);
		$tyr->run();
		$tyr->finish();
	}
	
	function get_values_array($process_hidden = true, $process_messages = true)
	{
		$values = array();
		foreach($this->get_element_names() as $element_name)
		{
			if (( $element_name == 'messages[0][to]') && $process_messages)
			{
				$values['messages'][0]['to'] = $this->get_value($element_name);
			}
			elseif (( $element_name == 'messages[all][next_page]' ) && $process_messages)
			{
				$values['messages']['all']['next_page'] = $this->get_value($element_name);
			}
			elseif (( $element_name == 'messages[all][form_title]' ) && $process_messages)
			{
				$values['messages']['all']['form_title'] =  $this->get_value($element_name);
			}
			elseif ( substr($element_name, 0, 9) == 'transform') 
				unset($element_name); // these are only used to transform labels for DB saves
			else
			{
				$type = $this->get_element_property($element_name, 'type');
				
				if (($type != 'comment') && (!(($type == 'hidden') && ($process_hidden == false))))
				{
					$values []= Array( 'label' => $this->get_element_property($element_name, 'display_name'),
									   'value' => $this->get_value($element_name), 
									  );
				}
			}
		}
		if(!empty($_SERVER[ 'REMOTE_USER' ]))
		{
			$values[] = array(	'label' => 'Authenticated Net ID of Submitter',
								'value' => $_SERVER[ 'REMOTE_USER' ],
							);
		}
		return $values;
	}
}

?>
