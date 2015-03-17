<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ 'giving/'.basename( __FILE__, '.php' ) ] = 'giveNowMiniModule';
	
	class giveNowMiniModule extends DefaultMinisiteModule
	{
		function init( $args = array() )
		{
			
		}

		function has_content()
		{
			return true;
		}

		function run()
		{

			?>
 
 	<div class="give-now-mini">
 		<div class="give-now-text">
	  		<div class="give-now-form">
		  		<h2><a data-options="align: bottom" data-dropdown="give-dropdown"><strong>Give</strong> Today</a></h2>
				<div id="give-dropdown" class="f-dropdown" data-dropdown-content>
					<div id="form">
						<form method="post" action="/x/" enctype="application/x-www-form-urlencoded" id="disco_form" name="disco_form" >
							<div id="discoLinear" class="thorTable">
								<div class="formElement" id="KPxIGpTV9nidItem">
									<div class="words"><span class="labelText">Gift Amount</span></div>
									<div class="element">
										<a name="KPxIGpTV9n_id_error"></a>
										<input type="text" name="KPxIGpTV9n_id" value="" size="30" maxlength="" placeholder="Enter amount" id="KPxIGpTV9n_idElement" class="text" />
									</div>
								</div>
								<div class="submitSection">
									<input type="submit" name="__button_submit" value=" Next Step " />
								</div>
							</div>
							<input type="hidden" name="submitted" value="true" />
						</form>
					</div>
				</div>
		  	</div>
		 </div>
  	</div>

			<?php
		}
	}
?>
