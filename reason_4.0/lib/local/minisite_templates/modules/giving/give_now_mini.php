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
		  		<!-- <h2><a data-options="align: bottom" data-dropdown="give-dropdown"><strong>Give</strong> Today</a></h2> -->
				<!-- <div id="give-dropdown" class="f-dropdown" data-dropdown-content> -->
				<!-- <div id="give-dropdown"> -->
					<div id="form">
						<form method="post" action="/x/" enctype="application/x-www-form-urlencoded" id="disco_form" name="disco_form" >
							<div id="discoLinear" class="thorTable">
								<div class="formElement" id="giftamountItem">
									<div class="words"><span class="labelText">Gift Amount</span></div>
									<div class="element">
										<a name="gift_amount_error"></a>
										<span class="currency">$</span> <input type="text" placeholder="Give today!" name="gift_amount" value="" size="50" maxlength="256" id="gift_amountElement" class="text">
									</div>
								</div>
								<div class="submitSection">
									<input type="submit" name="__button_submit" value=" Next Step " />
								</div>
							</div>
							<input type="hidden" name="submitted" value="true" />
						</form>
					</div>
				<!-- </div> -->
		  	</div>
		 </div>
  	</div>

			<?php
		}
	}
?>
