<?php

	/*
	 *	new url management solution
	 *	dave hendler, started 7/24/03
	 *
	 *	The goal of this system is to handle URLs in a nice way.  Instead of
	 *	having all URLs have ugly IDs and queries, we use mod_rewrite and
	 *	.htaccess files to hide the ugly URLs from sight.  This new method is
	 *	going to stray away from the central .htaccess file with thousands of
	 *	rules and instead have independent files for each site.
	 *
	 *	The url_manager class handles all of these things.  
	 */

	include_once( 'reason_header.php' );
	reason_include_once( 'classes/entity_selector.php' );

	class url_manager
	{
		var $site_id;	// site to work on
		var $web_root;	// root of the web tree
		var $debug = false;

		var $_fp;		// internal file pointer
		var $_rewrite_begin_str = 'reason-auto-rewrite-begin';	// beginning marker of rewrites
		var $_rewrite_end_str = 'reason-auto-rewrite-end';		// ending marker of rewrites
		
		// Specifies an external filepath to get the username and password for http authentication
		// Should include the local variables $http_authentication_username and $http_authentication_password
		var $http_credentials_settings_file = HTTP_CREDENTIALS_FILEPATH;
		
		function url_manager( $site_id, $debug = false, $do_global_rewrites = false ) // {{{
		{
			$this->debug = $debug;
			$this->debug( 'debugging is on' );

			if( !$do_global_rewrites && empty( $site_id ) )
				trigger_error( 'Site-specific url_manager called without a site_id in the constructor.  It needs one.', HIGH );

			// set up the directory that is the root of the web tree.
			$this->web_root = '/'.trim_slashes( !empty( $_SERVER[ '_' ] ) ? WEB_PATH : $_SERVER[ 'DOCUMENT_ROOT' ] ).'/';
			
			if(!empty($site_id))
			{
				$this->init_site($site_id);
			}
			else
			{
				$this->init_global();
			}
		} // }}}
		function init_site($site_id)
		{
			$this->site_id = $site_id;
			$this->site = new entity( $site_id, false );
			$this->site->refresh_values(false);

			$this->debug( 'Site id = '.$site_id.', '.$this->site->get_value( 'name' ) );

			if( !$this->site->get_value( 'base_url' ) )
				trigger_error( $this->site->get_value( 'name' ).' does not have a base_url.  This is bad for URL 
mapping.', HIGH );

			// full, absolute path to the root of the web tree
			$this->web_base_url = $this->site->get_value( 'base_url' );
			$this->full_base_url = $this->web_root.trim_slashes($this->site->get_value( 'base_url' ));
		}
		function init_global()
		{
			$this->debug( 'Global rewrites' );
			$this->web_base_url = '/'.trim_slashes(REASON_GLOBAL_FEEDS_PATH).'/';
			$this->full_base_url = $this->web_root.trim_slashes(REASON_GLOBAL_FEEDS_PATH);
		}
		function debug( $str ) // {{{
		{
			if( $this->debug )
				echo 'URL Manager: '.$str."<br />\n";
		} // }}}
		function update_rewrites() // {{{
		// this is the method that does all the work.
		{
			if( !empty($this->site) && $this->site->get_value('custom_url_handler') )
			{
				$this->debug( 'SKIPPED - CUSTOM URL HANDLER DEFINED: '.$this->site->get_value('custom_url_handler') );
				return;
			}
			if( $this->_is_base_valid( HIGH ) )
			{
				$this->debug( 'base is valid' );
				// this site's htaccess file
				$this->orig_file = $this->full_base_url.'/.htaccess';
				// temp directory and file to write new one into
				$this->test_dir_name = 'test_'.substr(md5(uniqid('')),0,10);
				$this->test_dir_web = $this->web_base_url.$this->test_dir_name;
				$this->test_dir = $this->full_base_url.'/'.$this->test_dir_name;
				// htaccess in the test directory
				$this->test_file = $this->test_dir.'/.htaccess';

				// make the new test directory
				if( !mkdir( $this->test_dir ) )
					trigger_error( 'Unable to make temp directory.', HIGH );
				$this->debug( 'Made test dir '.$this->test_dir );

				if( file_exists( $this->orig_file ) )
					$orig = file( $this->orig_file );
				else
					$orig = false;
				
				$this->debug( 'opening test file' );
				$this->_fp = fopen( $this->test_file, 'w' );
				if( !$this->_fp )
					trigger_error( 'Unable to open test htaccess file.', HIGH );

				// copy everything before unique string to new file if orig file exists
				if( !empty( $orig ) )
				{
					reset( $orig );
					while( list(,$line) = each( $orig ) )
					{
						if( preg_match('/'.$this->_rewrite_begin_str.'/', $line) )
							break;
						fputs( $this->_fp, $line ) OR trigger_error('Unable to write line to test file while copying beginning of original file.',HIGH);
					}
				}

				// begin comment marker and turn the engine on
				fputs( $this->_fp, '# '.$this->_rewrite_begin_str." !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n" ) OR trigger_error( 'Unable to write beginning of comment marker.',HIGH );
				fputs( $this->_fp, '# THIS SECTION IS AUTO-GENERATED - DO NOT TOUCH'."\n") OR trigger_error( 'Unable to write to htaccess.',HIGH );
				fputs( $this->_fp, '#!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!'."\n") OR trigger_error( 'Unable to write to htaccess.',HIGH );
				fputs( $this->_fp, 'RewriteEngine On'."\n" ) OR trigger_error('Unable to write RewriteEngine On',HIGH);
				//die($this->site->get_value( 'state' ));
				// call the specific update methods
				if(!empty($this->site))
				{
					if($this->site->get_value( 'state' ) == 'Live')
					{
						$this->_update_pages();
						$this->_update_assets();
						$this->_update_feeds();
					}
					else
					{
						fputs( $this->_fp, "\n".'# This site is '.$this->site->get_value( 'state' ).'.');
					}
				}
				else
				{
					$this->_update_global_feeds();
				}
		
				// end the comment marker for rules
				fputs( $this->_fp, "\n".'# '.$this->_rewrite_end_str." !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n" ) OR trigger_error('Unable to write to test rewrite file',HIGH);

				// copy rest of file if orig file exists and there is anything left to copy
				if( !empty( $orig ) )
				{
					// fast forward orig file to end of old rewrite rules
					while( list(,$line) = each($orig) AND !preg_match( '/'.$this->_rewrite_end_str.'/', $line ) )
						;

					// copy the rest of the old file to the new file
					while( list(,$line) = each($orig) )
						fputs( $this->_fp, $line ) OR trigger_error( 'Unable to write to test htaccess file', HIGH );
				}
			
				if( !fclose( $this->_fp ) )
					trigger_error( 'Unable to close test file.', HIGH );

				$are_files_diff = shell_exec('diff --brief '.$this->test_file.' '.$this->orig_file.' 2>&1');
				
				if( $are_files_diff )
				{
					$this->debug( 'test file diff from current, testing and copying' );
					$this->_test_and_copy();
				}
				else
				{
					$this->debug( 'test file not different. deleting test file' );
					if( !unlink( $this->test_file ) )
						trigger_error( 'Unable to delete test htaccess file.', WARNING );
				}
		
				// directory should be empty so we shouldn't need to recursively delete all
				if( !rmdir( $this->test_dir ) )
					trigger_error( 'Unable to delete test directory.', WARNING );

			}
			else
				$this->debug( 'base is not valid' );
		} // }}}
		function check_base() // {{{
		// simple wrapper for _is_base_valid with errors set to WARNING instead of HIGH
		{
			return $this->_is_base_valid( WARNING );
		} // }}}
		function make_site_valid() // {{{
		// function to either make a site's directory structure valid or at least report the steps necessary to do so
		{
			$ret = array();
			if( !$this->_is_base_valid( 0 ) AND (empty($this->site) || !$this->site->get_value('custom_url_handler') ) )
			{
				$this->debug( '<strong>site not set up correctly, gathering needed changes.</strong>' );
				if( !$this->_base_url_exists() )
				{
					$path = trim_slashes( $this->web_base_url );
					$path_parts = split( '/', $path );
					$working_dir = $this->web_root;
					foreach( $path_parts AS $part )
					{
						$working_dir .= $part.'/';
						if( !file_exists( $working_dir ) )
							$ret[] = 'mkdir '.$working_dir;
					}
					$ret[] = 'sudo chown '.REASON_SITE_DIRECTORY_OWNER.'.'.REASON_SITE_DIRECTORY_OWNER.' '.$working_dir.'<br />';
					$ret[] = 'sudo chmod 775 '.$working_dir.'<br />';
				}
				else if( !$this->_base_url_writable() )
				{
					$ret[] = 'sudo chown '.REASON_SITE_DIRECTORY_OWNER.'.'.REASON_SITE_DIRECTORY_OWNER.' '.$this->full_base_url.'<br />';
					$ret[] = 'sudo chmod 775 '.$this->full_base_url.'<br />';
				}
			}
			else
				$this->debug( '<strong>Site is set up properly.</strong>' );
			return $ret;
		} // }}}
		function _test_and_copy() // {{{
		{
			if( filesize( $this->test_file ) > 0 )
			{
				// this next line of code could replace the line above in case
				// it is needed.  Currently, I am just checking to see that the file has
				// a non-zero size.  A further check could see if the new file is at least
				// half the size of the original.  This may catch some things.  Don't
				// really know if it is necessary.  Anyway, the error code above
				// should catch most bad filesystem problems before we get here.
				//if( filesize( $new_path ) > (filesize( $orig_path ) / 2) )
				
				// here, we test to see if the .htaccess file is actually valid.
				// To do this, I copy the new file to a test directory, then hit
				// a file in that directory and see if the file loads or if there
				// is an Internal Server Error of type 500.  If we have a server
				// error, that means the file is not valid and someone needs to 
				// look at it.
				
				// Should include the variables $http_authentication_username and $http_authentication_password
				include($this->http_credentials_settings_file);
				$tmp_valid_file = '/tmp/'.uniqid( 'htvalid' );
				// create a quick test file to see if it shows up
				$tmp_test_index = $this->test_dir_name.'/index.html';
				$fp = fopen( $this->full_base_url.'/'.$tmp_test_index, 'w' ) OR trigger_error( 'Unable to create test index file', HIGH );
				fputs( $fp,'test successful' ) OR trigger_error( 'Unable to write to test index file', HIGH );
				fclose( $fp ) OR trigger_error( 'Unable to close test index file', HIGH );
				// use curl to grab the test index page
				$curl_cmd = 'curl ';

				// when used without the -k option, curl will fail if the server does not have a valid SSL cert.
				// ... -k allows curl to function without a valid certificate on a secure connection

				if( !REASON_HOST_HAS_VALID_SSL_CERTIFICATE ) $curl_cmd .= ' -k ';
				$curl_cmd .= ' -u'.$http_authentication_username.':'.$http_authentication_password.' https://'.REASON_HOST.$this->web_base_url.$tmp_test_index.' > '.$tmp_valid_file;

				exec( $curl_cmd, $curl_output, $curl_return_var );

				if( $curl_return_var > 0 )
					trigger_error( 'Unable to even try to validate .htaccess file - curl failed - '.$curl_return_var."\n\n$curl_output", HIGH );

				// compare the test index page with the downloaded one
				$diff_cmd = 'diff --brief '.$this->test_dir.'/index.html '.$tmp_valid_file;

				exec( $diff_cmd, $diff_result, $diff_return_var );

				if( $diff_return_var > 1 )
					trigger_error( 'Unable to determine if .htaccess validates or not - diff failed with return code '.$diff_return_var, HIGH );

				if( unlink( $tmp_valid_file ) === FALSE )
					trigger_error( 'Unable to delete tmp ht valid file', WARNING );
			
				// if empty, file did not validate
				if( $diff_result )
					trigger_error( $curl_cmd . ' .htaccess file did not validate.', HIGH );
		
				// make a backup of the original file
				if( !empty( $orig ) )
				{
					if( copy( $this->orig_file, $this->orig_file.'.bak') === FALSE )
						trigger_error( 'Unable to make a backup of the current htaccess', WARNING );
					// chmod so we can later write over the file
					if( chmod( $this->orig_file.'.bak', 0666 ) === FALSE )
						trigger_error( 'Could not chmod the backup htaccess file', WARNING );
				}
				// move the new file to the old position atomically
				if( rename( $this->test_file, $this->orig_file ) === FALSE )
					trigger_error( 'Unable to rename new rewrites file over old file', HIGH );
				// make it world writable so anyone can add rules or other stuff if need be
				if( !chmod( $this->orig_file, 0666 ) )
					trigger_error( 'Unable to chmod htaccess file', WARNING );
				// remove test temp index file
				if( !unlink( $this->full_base_url.'/'.$tmp_test_index ) )
					trigger_error( 'Unable to remove temporary index file.', WARNING );
			}
			else
				trigger_error('New rewrite .htaccess file has size 0.  Aborting rewrite updates.', HIGH);

			$this->debug('<strong>Updates complete.</strong>');
		} // }}}
		function _base_url_exists() // {{{
		{
			return file_exists( $this->full_base_url );
		} // }}}
		function _base_url_writable() // {{{
		{
			return is_writable( $this->full_base_url );
		} // }}}
		function _is_base_valid( $err_level = HIGH ) // {{{
		// checks for directory existance and correct permissions
		{
			if(!empty($this->site))
			{
				$site_name = $this->site->get_value( 'name' );
			}
			else
			{
				$site_name = 'Global Feeds';
			}
			// check to see if the base_url for the site actually exists
			if( !$this->_base_url_exists() )
			{
				if( $err_level ) trigger_error( 'Base URL for '.$site_name.' does not exist. It must be created for URLs on this site to work. Try <strong>mkdir '.$this->full_base_url.'; sudo chown '.REASON_SITE_DIRECTORY_OWNER.'.'.REASON_SITE_DIRECTORY_OWNER.' '.$this->full_base_url.'; sudo chmod 775 '.$this->full_base_url.'</strong>.', $err_level );
				return false;
			}
			// check to see if base_url is writable
			else if( !$this->_base_url_writable() )
			{
				if( $err_level ) trigger_error( 'Base URL for site '.$site_name.' is not word writable. Reason cannot write the .htaccess file.  An admin needs to run the following command: sudo chown '.REASON_SITE_DIRECTORY_OWNER.'.'.REASON_SITE_DIRECTORY_OWNER.' '.$this->full_base_url.'; sudo chmod 775 '.$this->full_base_url.'</strong>.', $err_level );
				return false;
			}
			$this->debug( 'base is valid in _is_base_valid' );
			return true;
		} // }}}
		function _update_pages() // {{{
		{
			$this->debug( 'updating page rewrites' );
			// get all minisite_pages on this site
			$pages = array();
			$es = new entity_selector( $this->site_id );
			$es->add_type( id_of( 'minisite_page' ) );
			$es->add_left_relationship_field( 'minisite_page_parent','entity','id','parent');
			$es->add_left_relationship_field( 'minisite_page_parent','entity','state','parent_state');
			$tmp =  $es->run_one();
		
			foreach( $tmp AS $page )
			{
				// we want to exclude pages whose parents are not part of the tree
				if($page->get_value('parent_state') == 'Live')
				{
					$pages[ $page->id() ][ 'parent' ] = $page->get_value( 'parent' );
					$pages[ $page->id() ][ 'url_fragment' ] = $page->get_value( 'url_fragment' );
					$pages[ $page->id() ][ 'id' ] = $page->id();
					$pages[ $page->id() ][ 'url' ] = $page->get_value( 'url' );
				}
			}

			unset( $tmp );

			// get all nice urls for all page_node pages
			foreach( $pages AS $page_id => $page )
				$pages[ $page_id ][ 'real_url' ] = $this->_build_url( $pages, $page_id );

			fputs( $this->_fp, "\n# minisite page rewrites\n\n" ) OR trigger_error( 'Unable to write to htaccess', HIGH );;

			foreach( $pages AS $page )
				if( empty( $page[ 'url' ] ) )
				{
					$nice = $this->_get_nice_url( $page );
					// fully qualified nice URL
					$fq_nice = $this->web_base_url.$nice;
					$ugly = $this->_get_minisite_ugly_url( $page );
					// if we have a nice URL, write the rules to the file
					if( !empty( $nice ) )
					{
						// make sure that the URL has a trailing slash.  This is required for Reason to properly display
						// links.  If the trailing slash is not appended (in the case of copy and paste or a bad link),
						// then any piece of display code that tries to show a relative link will be linking to files on
						// the wrong level.
						fputs( $this->_fp, 'RewriteRule ^'.$nice.'$ '.$fq_nice.'/ [R=permanent]'."\n" ) OR trigger_error( 'Unable to write to htaccess', ERROR );
						// The real, invisible switch to the minisite script that powers Reason
						fputs( $this->_fp, 'RewriteRule ^'.$nice.'/$ '.$ugly."\n" ) OR trigger_error( 'Unable to write to htaccess', HIGH );
					}
					// if $nice URL is empty, this is the root page of the site.  In this case, we want to act slightly
					// differently.  We don't want to append a slash or match on a slash.  Instead, we want to match on
					// nothing and that's it.
					else
					{
						fputs( $this->_fp, 'RewriteRule ^$ '.$ugly."\n" );
					}
				}
		} // }}}
		function _update_assets() // {{{
		{
			$this->debug( 'updating assets' );
			fputs( $this->_fp, "\n# asset rewrites\n\n" ) OR trigger_error('Unable to write to htaccess', HIGH );
			
			// path to asset_access_handler
			$asset_handler_path = REASON_HTTP_BASE_PATH.'displayers/asset_access.php';

			// get all assets for this site
			$es = new entity_selector( $this->site_id );
			$es->add_type( id_of( 'asset' ) );
			$assets = $es->run_one();

			// get assets that are related to a viewing group
			//$es->add_left_relationship_field('asset_access_permissions_to_group', 'entity', 'id', 'viewing_group_id');
			//$assets_with_permissions = $es->run_one();

			foreach( $assets AS $id => $asset )
			{
				$real_filename = $id.'.'.$asset->get_value('file_type');
				//if (isset($assets_with_permissions[$id]))
				fputs( $this->_fp, 'RewriteRule ^'.MINISITE_ASSETS_DIRECTORY_NAME.'/'.$asset->get_value('file_name').'$ '.$asset_handler_path.'?id='.$id."\n" ) OR trigger_error( 'Unable to write to htaccess file', HIGH );
				//else fputs( $this->_fp, 'RewriteRule ^'.MINISITE_ASSETS_DIRECTORY_NAME.'/'.$asset->get_value('file_name').'$ '.WEB_ASSET_PATH.$real_filename."\n" ) OR trigger_error( 'Unable to write to htaccess file', HIGH );
				$existing_asset_filenames[] = $asset->get_value('file_name');
			}
			
			// write redirects for old filenames
			$this->debug( 'updating asset archive rewrites' );
			fputs( $this->_fp, "\n# asset archive redirects\n\n" ) OR trigger_error('Unable to write to htaccess', HIGH );
			foreach( $assets as $asset )
			{
				$es = new entity_selector();
				$es->add_type( id_of('asset') );
				$es->add_right_relationship( $asset->id(), relationship_id_of('asset_archive') );
				$es->add_relation( 'asset.file_name != "'.$asset->get_value('file_name').'"' );
				$archived_assets = $es->run_one(false,'Archived');
				
				$archived_asset_filenames = array();
				foreach($archived_assets as $arch)
				{
					if(!in_array($arch->get_value('file_name'),$existing_asset_filenames) )
					{
						$existing_asset_filenames[] = $arch->get_value('file_name');
						fputs( $this->_fp, 'RewriteRule ^'.MINISITE_ASSETS_DIRECTORY_NAME.'/'.$arch->get_value('file_name').'$ '.$this->web_base_url.MINISITE_ASSETS_DIRECTORY_NAME.'/'.$asset->get_value('file_name').' [R=permanent]'."\n" ) OR trigger_error( 'Unable to write to htaccess', ERROR );
					}
				}
			}
			
		} // }}}
		function _build_url( &$pages, $page_id ) // {{{
		{
			if( isset( $pages[ $page_id ][ 'real_url' ] ) ) // we've already hit this one
			{
				return trim( $pages[ $page_id ][ 'real_url' ] );
			}
			elseif( $page_id == $pages[ $page_id ][ 'parent' ] ) // this is the home page
			{
				$pages[ $page_id ][ 'real_url' ] = $pages[ $page_id ][ 'url_fragment' ];
				return trim( $pages[ $page_id ][ 'real_url' ] );
			}
			else // crawl up the tree
			{

                // removed extra slashes on rewrite rule for pages that are children of external links
                return str_replace(array('^/','//'), array('^','/'), trim( $this->_build_url( $pages, $pages[ $page_id ][ 'parent' ] ) ).'/'.trim( $pages[ $page_id ][ 'url_fragment' ] ) );

				//return trim( $this->_build_url( $pages, $pages[ $page_id ][ 'parent' ] ) ).'/'.trim( $pages[ $page_id ][ 'url_fragment' ] );
			}
		} // }}}
		function _get_nice_url( $page ) // {{{
		{
			return trim_slashes( trim( $page['real_url'] ) );
		} // }}}
		function _get_minisite_ugly_url( $page ) // {{{
		{
			$script_url = $this->site->get_value( 'script_url' );
			if ( trim($script_url) == '' )
				$script_url = REASON_HTTP_BASE_PATH.'displayers/generate_page.php';
			return $script_url.'?site_id='.$this->site->id().'&page_id='.$page['id'];
		} // }}}
		function _update_feeds()
		{
			$this->debug( 'updating feeds' );
			fputs( $this->_fp, "\n# feed rewrites\n\n" ) OR trigger_error('Unable to write to htaccess', HIGH );

			// get all assets for this site
			$es = new entity_selector();
			$es->add_type( id_of( 'type' ) );
			$es->add_right_relationship( $this->site->id(), relationship_id_of('site_to_type') );
			$types = $es->run_one();

			foreach( $types as $type )
			{
				if($type->get_value('feed_url_string'))
					fputs( $this->_fp, 'RewriteRule ^'.MINISITE_FEED_DIRECTORY_NAME.'/'.$type->get_value('feed_url_string').'$ '.FEED_GENERATOR_STUB_PATH.'?type_id='.$type->id().'&site_id='.$this->site->id()."\n" ) OR trigger_error( 'Unable to write to htaccess file', HIGH );
			}
			
			$this->_update_blog_feeds();
		}
		function _update_blog_feeds()
		{
			$this->debug( 'updating blog feeds' );
			fputs( $this->_fp, "\n# blog feed rewrites\n\n" ) OR trigger_error('Unable to write to htaccess', HIGH );

			// get all assets for this site
			$es = new entity_selector( $this->site_id );
			$es->add_type( id_of( 'publication_type' ) );
			$blogs = $es->run_one();
			
			$blog_type_entity = new entity( id_of('publication_type') );
			$news_type_entity = new entity( id_of('news') );
			foreach( $blogs as $blog )
			{
				fputs( $this->_fp, 'RewriteRule ^'.MINISITE_FEED_DIRECTORY_NAME.'/'.$blog_type_entity->get_value('feed_url_string').'/'.$blog->get_value('blog_feed_string').'$ '.FEED_GENERATOR_STUB_PATH.'?type_id='.$news_type_entity->id().'&site_id='.$this->site->id().'&blog_id='.$blog->id().'&feed=blog_posts'."\n" ) OR trigger_error( 'Unable to write to htaccess file', HIGH );
			}
		}
		function _update_global_feeds()
		{
			$this->debug( 'updating global feeds' );
			fputs( $this->_fp, "\n# feed rewrites\n\n" ) OR trigger_error('Unable to write to htaccess', HIGH );

			// get all assets for this site
			$es = new entity_selector();
			$es->add_type( id_of( 'type' ) );
			$types = $es->run_one();

			foreach( $types as $type )
			{
				if($type->get_value('feed_url_string'))
					fputs( $this->_fp, 'RewriteRule ^'.$type->get_value('feed_url_string').'$ '.FEED_GENERATOR_STUB_PATH.'?type_id='.$type->id()."\n" ) OR trigger_error( 'Unable to write to htaccess file', HIGH );
			}
		}
	}

?>
