<?php
	reason_include_once( 'minisite_templates/modules/blurb.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'CallToActionBlurbModule';
	
	class CallToActionBlurbModule extends BlurbModule
	{

		function run() // {{{
		{
			$inline_editing =& get_reason_inline_editing($this->page_id);
			$editing_available = $inline_editing->available_for_module($this);
			$editing_active = $inline_editing->active_for_module($this);
			echo '<div class="blurbs ' . $this->get_api_class_string() . '">'."\n";
			$i = 0;
			$class = 'odd';
			foreach( $this->blurbs as $blurb )
			{

				if (preg_match("/[Cc]all [Tt]o [Aa]ction/", $blurb->get_value('name')))
				{

					$editable = ( $editing_available && $this->_blurb_is_editable($blurb) );
					$editing_item = ( $editing_available && $editing_active && ($this->request['blurb_id'] == $blurb->id()) );
					$i++;
					echo '<div class="blurb number'.$i;
					if($blurb->get_value('unique_name'))
						echo ' uname_'.htmlspecialchars($blurb->get_value('unique_name'));
					if( $editable )
						echo ' editable';
					if( $editing_item )
						echo ' editing';
					echo ' '.$class;
					echo '">';

					echo '<div class="blurbInner">';
					
					if($editing_item)
					{
						if($pages = $this->_blurb_also_appears_on($blurb))
						{
							$num = count($pages);
							echo '<div class="note"><strong>Note:</strong> Any edits you make here will also change this blurb on the '.$num.' other page'.($num > 1 ? 's' : '').' where it appears.</div>';
						}
						echo $this->_get_editing_form($blurb);
					}
					else
					{
						echo demote_headings($blurb->get_value('content'), $this->params['demote_headings']);
						if( $editable )
						{
							$params = array_merge(array('blurb_id' => $blurb->id()), $inline_editing->get_activation_params($this));
							echo '<div class="edit"><a href="'.carl_make_link($params).'">Edit Blurb</a></div>'."\n";
						}
					}

					echo '</div>'."\n";
					echo '</div>'."\n";
					$class = ('odd' == $class) ? 'even' : 'odd';
				}
			}
			echo '</div>'."\n";
		}

		function has_content()
		{
			if(!empty($this->blurbs))
			{
				foreach($this->blurbs as $blurb)
				{
					if (preg_match("/[Cc]all [Tt]o [Aa]ction/", $blurb->get_value('name')))
					{
						return true;
					}
				}
			}
			return false;
		}
		
	}
?>
