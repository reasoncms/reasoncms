<?php

reason_include_once('minisite_templates/modules/default.php');

$GLOBALS['_module_class_names'][basename(__FILE__, '.php')] = 'DjangoFormModule';

class DjangoFormModule extends DefaultMinisiteModule {

		function run() {
				echo 
					"<style type='text/css'>
						#form_wrapper { padding:20px; }
					</style>
					<script type='text/javascript' src='https://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js'></script>
					<script type='text/javascript' src='{{ STATIC_URL }}js/jquery.form.js'></script>
					<script type='text/javascript'>
						$(function(){  //jquery document ready
							$.ajax({
								url: 'http://localhost:8000/dorian_band_festival/director',
								success: function(data) {
									$('#form_wrapper').html(data);

									var options = {
										target:         '#form_wrapper',    //target element to be updated with server response
										beforeSubmit:   showRequest,        //pre-submit callback
										success:        showResponse        //post-submit callback
									}
									$('#director_form').ajaxForm(options);
								}
							});
						});
						function showRequest(formData, jqForm, options) {
							var queryString = $.param(formData);    //formData is an array, $.param converts to a string to display
							return true;    //return false to prevent the form from being submitted
						}
						function showResponse(responseText, statusText, xhr, \$form) {
							$('#form_wrapper').html(responseText);
						}
					</script>
				
					<div>
						<h2>Home</h2>
						<div id=\"form_wrapper\">
							the form will go here
						</div>
					</div>";
				

				return;
		}

}
?>
