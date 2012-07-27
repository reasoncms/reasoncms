<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'AdmissionsIndividualVisitModule';
	
	class AdmissionsIndividualVisitModule extends DefaultMinisiteModule
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
		<form id="myForm" action="comment.php" method="post"> 
    		Name: <input type="text" name="name" /> 
		    Comment: <textarea name="comment"></textarea> 
    		<input type="submit" value="Submit Comment" /> 
		</form>
<?php
		}
	}
?>