<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ 'giving/'.basename( __FILE__, '.php' ) ] = 'GiveNowModule';
	
	class GiveNowModule extends DefaultMinisiteModule
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
 
 	<div class="give-now">

	<div class="give-now-text">
  	
	<div class="give-now-form equal-height">
		<h2>Make a <strong>Difference</strong> Today</h2>
		  		
		<div id="form">					
		<div id="discoLinear" class="thorTable">
										
		<form class="startGift" action="/giving/givenow/" method="post" enctype="application/x-www-form-urlencoded">
		
			<div class="formElement" id="giftamountItem">
				<div class="words">
					<span class="labelText">Gift Amount</span>
				</div>
			<div class="element">
				<span class="currency">$</span><input type="text" placeholder="Enter amount" name="gift_amount" value="" size="50" maxlength="256" id="gift_amountElement" class="text">
			</div>
			</div>	
		
			<div class="formElement">
			<div class="element">	
				<div id="installment_type_container" class="radioButtons inLineRadioButtons">
				<!-- <table border="0" cellpadding="1" cellspacing="0"> -->
					<!-- <tr> -->
						<span class="radioItem"><input type="radio" id="radio_installment_type_1" name="installment_type" value="Monthly" /></span>
						<span class="radioItem"><label for="radio_installment_type_1">Every month</label></span>
					<!-- </tr>
					<tr> -->
						<span class="radioItem"><input type="radio" id="radio_installment_type_2" name="installment_type" value="Quarterly" /></span>
						<span class="radioItem"><label for="radio_installment_type_2">Every quarter</label></span>
					<!-- </tr>
					<tr> -->
						<span class="radioItem"><input type="radio" id="radio_installment_type_0" name="installment_type" value="Onetime" /></span>
						<span class="radioItem"><label for="radio_installment_type_0">One time</label></span>
					<!-- </tr>
				</table> -->
			</div>
			</div>
			</div>

			<div class="submitSection">
				<input type="submit" alt="Next Step" class="submit" value="Next Step">
			</div>
		</form>
					
		</div>
		</div>
		</div>
		
			<?php 		
		if ( 'giving_other_ways_to_give_blurb' == reason_unique_name_exists('giving_other_ways_to_give_blurb') )
		{
    		echo get_text_blurb_content('giving_other_ways_to_give_blurb');
		}
			?>

	</div>
	</div>

			<?php
		}
	}
?>
