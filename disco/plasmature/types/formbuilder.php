<?php

/**
 * Formbuilder type library.
 * @package disco
 * @subpackage plasmature
 */

require_once PLASMATURE_TYPES_INC."default.php";

/**
 * Thor
 * @package disco
 * @subpackage plasmature
 */

class formbuilderType extends textareaType
{
	var $type = 'formbuilder';

	function display()
	{
		echo '<textarea name="'.$this->name.'" rows="'.$this->rows.'" cols="'.$this->cols.'">'.htmlspecialchars($this->get(),ENT_QUOTES,'UTF-8').'</textarea>';
		?>
		<script>
			$(function(){
				options = {
					'useJson' : false
				}
				$('textarea[name="<?php echo $this->name; ?>"]').attachFormBuilder(options);
			});
		</script>
<?php

	}
}
