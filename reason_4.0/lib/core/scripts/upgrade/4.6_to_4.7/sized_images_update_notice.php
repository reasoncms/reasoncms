<?php
/**
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.6_to_4.7']['sized_images_update_notice'] = 'ReasonUpgrader_47_SizedImageUpdateNotice';
include_once('reason_header.php');
reason_include_once('classes/upgrade/upgrader_interface.php');

class ReasonUpgrader_47_SizedImageUpdateNotice extends reasonUpgraderDefault implements reasonUpgraderInfoInterface
{	
    /**
     * Return information about new settings
     * @return string HTML report
     */
	public function run()
	{
		$str = '<h3>Change to sized image directory structure</h3>'."\n";
		$str .= '<p>This version of Reason changes the structure of the sized_images directory
				to reduce the total number of subdirectories (some systems have a limit of 
				32,000 subdirectories in a single directory). If you do nothing, all of your
				sized images will be regenerated as needed.</p>'."\n";
		$str .= '<p>To save load on your server, however, you can manually reorganize
				the contents of your sized_images directory after installing this upgrade.
				(UNIX only) To do the reorganization, enter the sized_images directory, 
				<code>sudo su</code> to the web server user, and enter this command:</p>'."\n";
		$str .= '<p><code>ls -d1 * | sed \'s%^[0-9]*\([0-9]\{3\}$\)%mkdir -p "set\1" ; mv "&" "set\1/"%e\'</code></p>'."\n";
		$str .= '<p>Depending on the number of directories in your sized_images folder, this
				could take a long time to run (up to 1 minute per 1000 directories). </p>'."\n";
		$str .= '<p>You may also want to add a .htaccess file to your sized_images directory to
				rewrite any requests for images in their old locations. Use this example, 
				updating the web path as needed:</p>'."\n";
		$str .= '<p><code>RewriteEngine On<br />
RewriteRule ^([0-9]*([0-9][0-9][0-9]))/(.*\..*)$ /reason_package/reason_4.0/www/sized_images/set$2/$1/$3 [R]</code></p>'."\n";
		$str .= '<p><strong>Note:</strong> Both of these examples assume that all of your image
				ids are at least 3 digits. That should be the case in most Reason installs. If
				your case is different, you may need to make some adjustments.</p>'."\n";
		return $str;
	}
}
?>