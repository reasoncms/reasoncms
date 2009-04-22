Welcome to Reason 4 Beta 8!
http://apps.carleton.edu/opensource/reason/

Install
-------
The reason_package directory should be placed outside the web tree.

If it is in the web tree, please move it to a location outside the web tree before proceeding.

Next, make a symbolic link within the base directory of your web tree called reason that
links to the reason_package/reason_4.0/www/ directory.

The syntax on unix / linux / mac os x should look like this:

ln -s /path/to/reason_package/reason_4.0/www/ /path/to/web/root/reason

Full instructions which explain how to manually setup reason are located here:
http://your.web.server/reason/install.htm

If you are feeling brave or have experience with reason, you may want to just try the setup script:
http://your.web.server/reason/setup.php 

Upgrade
-------
Reason 4 Beta 8 supports (and recommends) that you place the reason_package outside of the web tree.

Prior versions of Reason only theoretically supported this type of setup.

The file reason_package/reason_upgrade.php distributed with Reason 4 Beta 8 contains information on
upgrading from Reason 4 Beta 7 and suggestions on how to move your reason install outside of the
web tree.



