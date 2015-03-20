<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ 'giving/'.basename( __FILE__, '.php' ) ] = 'giveNowModule';
	
	class giveNowModule extends DefaultMinisiteModule
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
					
					<form method="post" action="/x/" enctype="application/x-www-form-urlencoded" id="disco_form" name="disco_form" >
					<div id="discoLinear" class="thorTable">
					
					<div class="formElement" id="giftamountItem">
					  <div class="words"><span class="labelText">Gift Amount</span></div>
					  <div class="element">
					    <a name="gift_amount_error"></a>
					    <span class="currency">$</span> <input type="text" placeholder="Enter amount" name="gift_amount" value="" size="50" maxlength="256" id="gift_amountElement" class="text">   </div>
					</div>

					<div class="formElement" id="lyUo3sK6b6idItem">
					<div class="words"><span class="labelText">Frequency</span></div>
					<div class="element">
					<a name="lyUo3sK6b6_id_error"></a>
					<div id="lyUo3sK6b6_id_container" class="radioButtons">
					<table border="0" cellpadding="1" cellspacing="0">
					<tr>
					<td valign="top"><input type="radio" id="radio_lyUo3sK6b6_id_0" name="lyUo3sK6b6_id" value="One time" /></td>
					<td valign="top"><label for="radio_lyUo3sK6b6_id_0">One time</label></td>
					</tr>
					<tr>
					<td valign="top"><input type="radio" id="radio_lyUo3sK6b6_id_1" name="lyUo3sK6b6_id" value="Every month" /></td>
					<td valign="top"><label for="radio_lyUo3sK6b6_id_1">Every month</label></td>
					</tr>
					<tr>
					<td valign="top"><input type="radio" id="radio_lyUo3sK6b6_id_2" name="lyUo3sK6b6_id" value="Every quarter" /></td>
					<td valign="top"><label for="radio_lyUo3sK6b6_id_2">Every quarter</label></td>
					</tr>
					<tr>
					<td valign="top"><input type="radio" id="radio_lyUo3sK6b6_id_3" name="lyUo3sK6b6_id" value="Every year" /></td>
					<td valign="top"><label for="radio_lyUo3sK6b6_id_3">Every year</label></td>
					</tr>
					</table>
					</div>
					</div>
					</div>
					<div class="submitSection">
					<input type="submit" name="__button_submit" value=" Next Step " /></div></div><input type="hidden" name="submitted" value="true" />
					</form>
					</div>
		  	</div>

		  	<div class="other-ways equal-height">
				<h3><a href="#">Other Ways to Give</a></h3>
				<ul>
					<li>Phone</li>
					<li>Mail</li>
					<li>Electronic Funds Transfer</li>
					<li>Stock Transfer</li>
					<li>Payroll Deduction</li>
					<li>Employer Matching Gifts</li>
					<li>Planned Gifts</li>
				</ul>

				<h3><a href="#">Gift Types / Areas of Support</a></h3>
		  		<ul>
		  			<li>Annual Fund Gifts</li>
		  			<li>Reunion Gifts</li>
		  			<li>Senior Giving Campaign</li>
		  			<li>Planned Gifts</li>
		  			<li>Memorial/Honorary Gifts</li>
		  			<li>Norse Athletic Association Memberships</li>
		  			<li>Endowment/Scholarship Support</li>
		  		</ul>
		  	</div>

		 </div>
  	</div>

			<?php
		}
	}
?>
