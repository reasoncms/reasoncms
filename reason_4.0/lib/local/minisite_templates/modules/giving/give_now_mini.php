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
					<div id="form">
						<form method="post" action="/giving/givenow/" enctype="application/x-www-form-urlencoded" id="disco_form" name="disco_form" >
							<div id="discoLinear" class="thorTable">
								<div class="formElement" id="giftamountItem">
									<div class="words"><span class="labelText">Gift Amount</span></div>
									<div class="element">
										<span class="currency">$</span>
										<input type="text" placeholder="Give today!" name="gift_amount" value="" size="50" maxlength="256" id="gift_amountElement" class="text">
									</div>
								</div>
								<div class="submitSection">
									<input type="submit" alt="Next Step" class="submit" value="Next Step">
								</div>
							</div>
						</form>
					</div>
		  	</div>
		 </div>
  	</div>

			<?php
		}
	}
?>
