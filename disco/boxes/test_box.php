<?php
	/**
	* @package disco
	* @subpackage boxes
	*/

	class testBox extends Box// {{{
	{
		function head() // {{{
		{
			?>
			<table border="0" cellpadding="6" cellspacing="0">
			<?php
		} // }}}
		function row_open( $label, $required = false, $error = false ) // {{{
		{
			?>
			<tr valign="top">
				<td align="right" class="smallText" bgcolor="#CCCCCC">&nbsp;&nbsp;&nbsp;<?php echo $label; ?>:<?php if ( $required ) echo '*'; ?></td>
				<td align="left" class="smallText" bgcolor="#CCCCCC">
			<?php
		} // }}}
		function row_close() // {{{
		{
			?>
				</td>
			</tr>
			<?php
		} // }}}
		function row( $label, $content, $required = false, $error = false ) // {{{
		{
			$this->row_open( $label, $required, $error );
			echo $content;
			$this->row_close();
		} // }}}
		function foot( $buttons = '' ) // {{{
		{
			if ( $buttons )
			{
				if( !is_array( $buttons ) )
				{
					$tmp = $buttons;
					$buttons = array();
					$buttons[$tmp] = $tmp;
				}
				?>
				<tr>
					<td align="right">&nbsp;</td>
					<td align="left">
				<?php
				reset( $buttons );
				while( list( $name,$value ) = each( $buttons ) )
				{
					?>
					<input type="submit" name="<?php echo $name; ?>" value=" <?php echo $value ?> " />&nbsp;&nbsp;
					<?php
				}
				?>
					</td>
				</tr>
				<?php
			}
			?>
			</table>
			<?php
		} // }}}
	} // }}}
?>
