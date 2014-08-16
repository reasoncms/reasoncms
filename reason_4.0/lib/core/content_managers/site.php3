<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'SiteManager';
	
	include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
	reason_include_once( 'classes/url_manager.php' );
	reason_include_once( 'classes/page_cache.php' );
	reason_include_once( 'classes/title_tag_parser.php' );
	reason_include_once( 'function_libraries/root_finder.php');
	reason_include_once( 'function_libraries/URL_History.php');
	
	/**
	 * Content manager for sites
	 *
	 * @todo remove the _is_element checks prior to Reason 4 RC 1
	 */
	class SiteManager extends ContentManager
	{
		var $old_entity_values = array();
		
		function init_head_items()
		{
			$this->head_items->add_javascript(JQUERY_URL, true); // uses jquery - jquery should be at top
			$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH .'content_managers/site.js');
		}
		
		function alter_data() // {{{
		{
			$this->_no_tidy[] = 'theme_customization';
			$this->remove_element( 'theme_customization' );
			
			// don't allow the user to see whether the site is new or not
			if ($this->_is_element('is_incarnate')) $this->remove_element( 'is_incarnate' );
			
			if (!$this->_is_element('domain'))
			{
				$link = REASON_HTTP_BASE_PATH . 'scripts/upgrade/4.0b7_to_4.0b8/update_types.php';
				trigger_error('The site type does not have the domain field. Please run the reason 4.0b7_to_4.0b8 upgrade script "<a href="'.$link.'">update_types</a>."');
			}
			else $this->setup_multidomain_support();
			
			$old_entity = new entity( $this->get_value('id'), false );
			$this->old_entity_values = $old_entity->get_values();
				
			$this->change_element_type(
				'use_page_caching',
				'select',
				array(
					'options' => array(
						0 => 'Off',
						1 => 'On' 
					),
					'add_empty_value_to_top' => false,
				)
			);
			$this->add_required( 'unique_name' );

			// make the form easier to read
			$this->change_element_type( 'short_department_name','hidden');
			$this->set_display_name( 'department','Department Code/ID');
			$this->set_display_name( 'custom_url_handler','Custom URL Handler');
			$this->set_display_name( 'use_page_caching', 'Page Caching' );
			$this->set_comments( 'primary_maintainer',form_comment('Username of maintainer') );
			$this->set_comments( 'base_url',form_comment( 'Path to your site.<br />eg: /campus/multicultural<br /><span style="color: #f33"><strong>Warning:</strong> If a site already occupies the URL, it will get clobbered.</span>' ) );
			$this->set_comments( 'department',form_comment('(If integrated) The office or department code or ID in central information system (e.g. LDAP, etc.)') );
			//$this->set_comments( 'custom_url_handler', form_comment('Give a descriptive value if this site will NOT use Reason\'s default URL management code.') );
			$this->change_element_type( 'custom_url_handler','hidden');
			$this->change_element_type( 'use_custom_footer','select_no_sort',array('display_name'=>'Footer Type','options'=>array('no'=>'Standard','yes'=>'Custom'),'add_empty_value_to_top' => false,));
			if(!$this->get_value('use_custom_footer'))
			{
				$this->set_value('use_custom_footer','no');
			}
			if(defined('REASON_DEFAULT_FOOTER_XHTML') && $this->get_value('use_custom_footer') == 'no' && !$this->get_value('custom_footer'))
			{
				$this->set_value('custom_footer',REASON_DEFAULT_FOOTER_XHTML);
			}
			$this->set_comments( 'use_page_caching', form_comment('<strong>Note:</strong> page caching may make changes to your pages be delayed up to 1 hour after they are made.  Only turn this on if your site has a lot of traffic and you need to improve performance.') );
			
			$t = new TitleTagParser(null, null);

			$this->set_comments( 'home_title_pattern', form_comment('<strong>Tags:</strong> '. join(', ', $t->tags) .'. Be sure to wrap the tag in <strong>straight brackets: [tag]</strong> The default value for this field is '. REASON_HOME_TITLE_PATTERN));
			$this->set_comments( 'secondary_title_pattern', form_comment('The default value for this field is '. REASON_SECONDARY_TITLE_PATTERN));
			$this->set_comments( 'item_title_pattern', form_comment('The default value for this field is '. REASON_ITEM_TITLE_PATTERN));

			$this->add_element('title_patterns_header','comment',array('text'=>'<h3>Page Titles</h3><p class="smallText">These tags are replaced: ['. join('] [', $t->tags) .']. To change defaults across Reason, edit settings/reason_settings.php.</p>'));
			
			if('' == $this->get_value('home_title_pattern'))
			{
				$this->set_value('home_title_pattern', REASON_HOME_TITLE_PATTERN);
				$this->add_comments( 'home_title_pattern',' <em class="smallText">(Default)</em>');
			}
			else
			{
				$this->add_comments( 'home_title_pattern',' <em class="smallText">(Customized; clear to return to default)</em>');
			}
			if('' == $this->get_value('secondary_title_pattern'))
			{
				$this->set_value('secondary_title_pattern', REASON_SECONDARY_TITLE_PATTERN);
				$this->add_comments( 'secondary_title_pattern',' <em class="smallText">(Default)</em>');
			}
			else
			{
				$this->add_comments( 'secondary_title_pattern',' <em class="smallText">(Customized; clear to return to default)</em>');
			}
			if('' == $this->get_value('item_title_pattern'))
			{
				$this->set_value('item_title_pattern', REASON_ITEM_TITLE_PATTERN);
				$this->add_comments( 'item_title_pattern',' <em class="smallText">(Default)</em>');
			}
			else
			{
				$this->add_comments( 'item_title_pattern',' <em class="smallText">(Customized; clear to return to default)</em>');
			}
			
			$this->add_comments( 'home_title_pattern',form_comment('Used for the site\'s home page'));
			$this->add_comments( 'secondary_title_pattern',form_comment('Used for all other pages on the site'));
			$this->add_comments( 'item_title_pattern',form_comment('Used for posts, events, and other non-page items'));
			
			$this->set_comments( 'keywords',form_comment('These words or phrases will be used by the A-Z module to provide a keyword index of Reason sites. Separate words and phrases with a comma, like this: <em>Economics, Monetary Policy, Political Economy</em>'));
			$this->set_comments( 'site_state',form_comment('The current status of the site. "Live" sites are listed in the A-Z and Sitemap modules, and cannot borrow items from "Not Live" sites. "Not Live" sites are hidden from search engines and do not appear in listings of live sites. When you are building a site you probably want it to be "Not Live," and when it is ready for primetime you should set it to be "Live" so it can be indexed.'));
			$this->set_comments( 'other_base_urls',form_comment('This field is used by the stats integration feed to identify other directories whose stats you want to see along with this site\'s. Enter URLs relative to the server base, separated by commas (e.g. <em>/foo/bar/, /bar/foo/</em>.) You can ignore this field if you are not running a stats package integrated with Reason.'));
			$this->set_comments( 'name',form_comment('The name of the site.'));
			$this->set_comments( 'unique_name',form_comment('A stable textual identifier for the site. This should be a url-safe string -- no spaces, weird characters, etc.'));
			$this->set_comments( 'base_breadcrumbs',form_comment('If you want to add breadcrumb navigation before the root level of the site, place initial breadcrumb html here. <br />Example: &lt;a href="/asite/"&gt;A site&lt;/a&gt; &amp;gt; &lt;a href="/asite/another/"&gt;Another Site&lt;/a&gt;'));
			$this->set_comments( 'description',form_comment('A general description of the site. This is shown when administrators log in to the site, and in the child_sites module.'));
			// a temporary fix until we get assets settled
			$this->set_value( 'asset_directory','asset' );
			$this->change_element_type( 'asset_directory','hidden' );

			// get rid of archaic fields
			if ($this->_is_element('script_url')) $this->remove_element( 'script_url' );

			// check for valid data
			$this->add_required( 'base_url' );
			$this->add_required( 'primary_maintainer' );
			
			// Make sure site is given a unique name for stats & other stuff
			$this->add_required( 'unique_name' );
			//$this->add_required( 'site_type' );
			
			$this->alter_editor_options_field();
			
			$this->add_relationship_element('theme', id_of('theme_type'), 
			relationship_id_of('site_to_theme'),'right','select');
			
			$this->add_relationship_element('site_type', id_of('site_type_type'), 
			relationship_id_of('site_to_site_type'));

			// if this is a new site, set the loki buttons to 'no tables'
			if( $this->is_new_entity() )
				$this->set_value( 'loki_default','notables' );

			$this->set_comments( 'loki_default',form_comment('The HTML editor options available when editing content on the site.'));
			$this->set_order(array('name','unique_name','primary_maintainer','base_url','domain','base_breadcrumbs','description','keywords','department','site_state','loki_default','other_base_urls','use_page_caching','theme','allow_site_to_change_theme','site_type','use_custom_footer','custom_footer','title_patterns_header','home_title_pattern','secondary_title_pattern','item_title_pattern',));
		} // }}}
		function alter_editor_options_field()
		{
			$options = html_editor_options($this->get_value('id'));
			
			if(!empty($options))
			{
				$this->change_element_type( 'loki_default','select_no_sort',array( 'options' => $options ) );
				$this->set_display_name('loki_default',prettify_string(html_editor_name($this->get_value('id'))).' Options');
			}
			else
			{
				$this->change_element_type( 'loki_default','hidden');
			}
		}
		function setup_multidomain_support()
		{
			// if we have defined multiple domains, present them as a dropdown.
			// if a domain is set but not defined in _reason_domain_settings, lets call out this situation
			if (isset($GLOBALS['_reason_domain_settings']) && 
				!empty($GLOBALS['_reason_domain_settings']) &&
				isset($GLOBALS['_default_domain_settings']["HTTP_HOST_NAME"]))
			{
				foreach ($GLOBALS['_reason_domain_settings'] as $k => $v)
				{
					if ($k != $GLOBALS['_default_domain_settings']["HTTP_HOST_NAME"]) $domains[$k] = $k;
				}
			}
			if (isset($domains)) // if there are option other than the default domain, present a choice
			{
				$this->change_element_type('domain', 'select', array('options' => $domains));
				$this->set_display_name('domain', 'Custom Domain');
				$this->set_comments('domain', form_comment('You should probably leave this alone for now - custom domains are an experimental feature introduced in Reason 4 Beta 8'));
			}
			else $this->remove_element('domain');
		}
		
		function run_error_checks() // {{{
		{
		
			if( !$this->has_error( 'primary_maintainer' ) )
			{
				// Make sure the primary maintainer exists in the directory
				$dir = new directory_service();
				if (!$dir->search_by_attribute('ds_username', $this->get_value('primary_maintainer')))
					$this->set_error( 'primary_maintainer','Invalid username for primary maintainer' );
			}

			// check for spaces
			if( !$this->has_error( 'base_url' ) )
			{
				if( !preg_match( '|^[a-z0-9_\-/]*$|i', $this->get_value('base_url') ) )
				{
					$this->set_error( 'base_url', 'Your base URL contains illegal characters. Allowable characters are letters, numbers, hyphens, underscores, and slashes.' );
				}
			}
			
			if( $this->get_value('use_custom_footer') != 'yes' )
			{
				$this->set_value('custom_footer','');
			}
			
			if(REASON_HOME_TITLE_PATTERN == $this->get_value('home_title_pattern'))
				$this->set_value('home_title_pattern', '');
			if(REASON_SECONDARY_TITLE_PATTERN == $this->get_value('secondary_title_pattern'))
				$this->set_value('secondary_title_pattern', '');
			if(REASON_ITEM_TITLE_PATTERN == $this->get_value('item_title_pattern'))
				$this->set_value('item_title_pattern', '');


			// file/dir check - don't overwrite real files
			if( !$this->has_error( 'base_url' ) AND $this->get_value('base_url') )
			{
				$clean_url = '/'.trim_slashes($this->get_value('base_url')).'/';
				$clean_url = str_replace('//','/',$clean_url);
				$this->set_value ('base_url', $clean_url);
				//check against other base_urls
				$es = new entity_selector();
				$es->add_type( id_of( 'site' ) );
				$es->add_relation('base_url = "'.$this->get_value('base_url').'"');
				$es->add_relation('entity.id != "'.$this->get_value('id').'"');
				$es->set_num( 1 );
				$sites = $es->run_one();
				if(!empty($sites))
				{
					$site = current($sites);
					$this->set_error('base_url','The site <strong>'.$site->get_value('name').'</strong> already has the base url '.$site->get_value('base_url').'. Base URLs must be unique.');
					$this->add_comments( 'base_url', form_comment('<span style="color: #f00"><strong>'.$site->get_value('name').'</strong> already has that base url.  Please use another.</span>' ) );
				}
			}
			
			if ($this->_is_element('domain') && !$this->has_error('domain'))
			{
				$cur_value = $this->get_value('domain');
				if ( !empty($cur_value) && !isset($GLOBALS['_reason_domain_settings'][$cur_value]) )
				{
					$this->set_error('domain', form_comment('The current value for domain (' . $cur_value . ') is not defined in domain_settings.php. Please choose a new custom domain.'));
				}
			}
		} // }}}
		
		function process() // {{{
		{
			$first_time = empty($this->old_entity_values['base_url']);

			if($first_time) // a new site
			{
				// create site entry
				$site_id = $this->get_value('id');

				// add the logged in user to the site
				create_relationship( $site_id, $this->admin_page->user_id, relationship_id_of( 'site_to_user' ) );
				if($this->get_value( 'primary_maintainer' ))
				{
					$primary_maintainer_user_id = get_user_id( $this->get_value( 'primary_maintainer' ) );
					if( !empty($primary_maintainer_user_id) && $primary_maintainer_user_id != $this->admin_page->user_id )
					{
						create_relationship( $site_id, $primary_maintainer_user_id , relationship_id_of( 'site_to_user' ) );
					}
				}
				
				// add the page,image, and blurb modules
				create_relationship( $site_id, id_of('minisite_page'), relationship_id_of('site_to_type'));
				create_relationship( $site_id, id_of('image'), relationship_id_of('site_to_type'));
				create_relationship( $site_id, id_of('text_blurb'), relationship_id_of('site_to_type'));

				// create root page and set it as its own parent
				$root_page = reason_create_entity( $site_id, id_of('minisite_page'), $this->admin_page->user_id, $this->get_value('name'),array('nav_display'=>'Yes','new'=>'0'));
				create_relationship( $root_page, $root_page, relationship_id_of('minisite_page_parent') );
				
				$this->create_base_dir();
			}
			elseif( $this->old_entity_values['base_url'] != $this->get_value('base_url') ) // try to move the directory if it has changed
			{
				$this->move_base_dir($this->old_entity_values['base_url'], $this->get_value('base_url'));
			}
			
			$page_cache = $this->get_value('use_page_caching');
			
			if (empty($page_cache))
			{
				$rpc = new ReasonPageCache();
				$rpc->set_site_id($this->get_value('id'));
				if ($rpc->site_cache_exists())
				{
					$attempt_delete = $rpc->delete_site_cache();
					if (!$attempt_delete)
					{
						trigger_error('A cache exists for the site and Reason cannot delete it. You should manually delete the folder ' . $rpc->get_site_cache_directory());
					}
				}
			}
			parent::process();
		} // }}}
		
		function finish()
		{
			if ($this->get_value('state') == 'Live') // we only want to do this if this is a live entity ... 
			{
				if (($this->old_entity_values['domain'] != $this->get_value('domain')) || ($this->old_entity_values['base_url'] != $this->get_value('base_url')))
				{
					$this->update_site_url_history();
				}
			}
			reason_include_once('classes/object_cache.php');
			$cache = new ReasonObjectCache($this->get_value('id') . '_navigation_cache');
			$cache->clear();
		}
		
		function create_base_dir()
		{
			$um = new url_manager( $this->get_value( 'id'));
			$um->update_rewrites();
		}
		
		/**
		 * This function does not truly move the base dir - here is what it does do
		 *
		 * - makes a directory at the new location if it does not exist
		 * - rewrites the .htaccess file at the current location to have any original custom .htaccess stuff plus the reason push_moved_site stuff
		 * - adds a callback so after the entity is saved the rewrites are created at the new base url
		 *
		 */
		function move_base_dir($old_base_dir, $new_base_dir)
		{
			if(!empty($old_base_dir) && !empty($new_base_dir))
			{
				$old_path = reason_get_site_web_path( $this->get_value('id') ).trim_slashes($old_base_dir);
				$new_path = reason_get_site_web_path( $this->get_value('id') ).trim_slashes($new_base_dir);
				if(!is_dir($new_path))
				{
					include_once(CARL_UTIL_INC.'basic/filesystem.php');
					mkdir_recursive($new_path, 0775);
				}
				
				// lets add the push_moved_site stuff to the current .htaccess file
				$file_contents = '';
				$orig = file($old_path.'/.htaccess');
				if( !empty( $orig ) )
				{
					reset( $orig );
					while( list(,$line) = each( $orig ) )
					{
						if( preg_match('/reason-auto-rewrite-begin/', $line) )
							break;
						$file_contents .= $line;
					}
				}
				$file_contents .= '# reason-auto-rewrite-begin !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!'."\n";
				$file_contents .= '# THIS SECTION IS AUTO-GENERATED - DO NOT TOUCH'."\n";
				$file_contents .= '#!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!'."\n\n";
				$file_contents .= 'RewriteEngine On'."\n\n";
				$file_contents .= 'RewriteRule ^$ '.REASON_HTTP_BASE_PATH.'displayers/push_moved_site.php?id='.$this->get_value('id')."\n\n";
				$file_contents .= '# reason-auto-rewrite-end !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!'."\n";
					
				$handle = fopen($old_path.'/.htaccess', 'w');
				fputs($handle, $file_contents);
				fclose($handle);
				chmod($old_path.'/.htaccess', 0664);
				
				// lets throw down a process callback method to update the urls (that way it happens AFTER entity save)
				$this->add_callback(array($this, 'update_rewrites'),'process');
			}
		}
		
		function run_custom_finish_actions( $new_entity = false )
		{
			if ($new_entity) // this is the first finish of a new entity - lets create its URL_history
			{
				$this->update_site_url_history();	
			}
		}

		function update_rewrites()
		{
			$um = new url_manager( $this->get_value( 'id'));
			$um->update_rewrites();
		}
		
		function update_site_url_history()
		{
			$page_id = root_finder( $this->get_value('id') );
			update_URL_history($page_id);
		}
	}
?>
