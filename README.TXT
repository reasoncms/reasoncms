Welcome to Reason!
http://apps.carleton.edu/opensource/reason/

Install
-------
The reason_package directory should be placed outside the web tree.

If it is in the web tree, please move it to a location outside the web tree before proceeding.

Next, make a symbolic link within the base directory of your web tree called reason that
links to the reason_package/reason_4.0/www/ directory.

The syntax on unix / linux / mac os x should look like this:

ln -s /path/to/reason_package/reason_4.0/www/ /path/to/web/root/reason

You also should make a symbolic link within the base directory of your web tree called 
reason_package that links to the reason_package/www/ directory

ln -s /path/to/reason_package/www/ /path/to/web/root/reason_package

Full instructions which explain how to manually setup reason are located here:
http://your.web.server/reason/install.htm

If you are feeling brave or have experience with Reason, you may want to just try the setup script:
http://your.web.server/reason/setup.php

Upgrade
-------
If you are upgrading, visit the following page and run the appropriate upgrade scripts:
http://your.web.server/reason/upgrade.php






