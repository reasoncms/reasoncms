<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
/**
 * Include the parent class & dependencies, and register the module with Reason
 */
reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'classes/quote_helper.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'QuoteModule';
	
/**
 * The quote module displays a single random quote, and provides an option to refresh the quote
 *
 * If changing class names or the HTML generation structure, make sure to modify the html generation
 * portions of quote_retrieve.js so that dynamically created quotes maintain the same structure.
 * 
 * @author Nathan White
 */
	class QuoteModule extends DefaultMinisiteModule
	{
		var $quotes;
		var $acceptable_params = array ('page_category_mode' => false,
										'cache_lifespan' => 0,
										'num_to_display' => NULL,
										'enable_javascript_refresh' => false,
										'prefer_short_quotes' => false,
										'rand_flag' => false,
										'template' => '&#8220;[[quote]]&#8221; [[divider]][[author]]',
										'quote_divider' => '',
										'footer_html' => '',
										'header_html' => '');
		
		function init( $args = array() )
		{	
			$qh = new QuoteHelper($this->site_id, $this->page_id);
			if ($this->params['quote_divider']) $qh->set_quote_divider($this->params['quote_divider']);
			if ($this->params['cache_lifespan'] > 0) $qh->set_cache_lifespan($this->params['cache_lifespan']);
			if ($this->params['page_category_mode']) $qh->set_page_category_mode($this->params['page_category_mode']);
			$qh->init();
			
			// javascript refresh mode currently forces display to a single quote
			$num_to_display = ($this->params['enable_javascript_refresh']) ? 1 : $this->params['num_to_display'];
			$this->quotes =& $qh->get_quotes($num_to_display, $this->params['rand_flag']);
			$this->init_head_items();
		}
		
		function init_head_items()
		{
			if ($this->quotes && $this->params['enable_javascript_refresh'])
			{
				$quote = current($this->quotes);
				$quote_id = $quote->id();
				$page_cat_mode = ($this->params['page_category_mode']) ? 1 : 0;
				$prefer_short_quotes = ($this->params['prefer_short_quotes']) ? 1 : 0;
				
				$cache_lifespan = ($this->params['cache_lifespan'] > 0) ? $this->params['cache_lifespan'] : 0;
				
				// all these parameters are sent in integer format so the javascript can accept only numeric params for security
				$qry_string = '?site_id='.$this->site_id.
							  '&page_id='.$this->page_id.
							  '&quote_id='.$quote_id.
							  '&page_category_mode='.$page_cat_mode.
							  '&cache_lifespan='.$cache_lifespan.
							  '&prefer_short_quotes='.$prefer_short_quotes;
				
				if ($head_items =& $this->get_head_items())
				{
					$head_items->add_javascript($this->get_head_item_base_path() . JQUERY_URL);
					$head_items->add_javascript($this->get_head_item_base_path() . REASON_HTTP_BASE_PATH . 'js/quote/quote_retrieve.js'.$qry_string); // pass params in qry string
				}
			}
		}
		
		/**
		 * For most instances we don't want a base path so this returns an empty string in the base module.
		 */
		function get_head_item_base_path()
		{	
			return '';
		}
		
		function has_content()
		{
			if( !empty($this->quotes) )
				return true;
			else
				return false;
		}
		
		function run()
		{
			echo '<div id="quotes">';
			if (!empty($this->params['header_html']))
			{
				echo '<div id="quotes_header">'."\n";
				if (!empty($this->params['header_html'])) echo ($this->params['header_html']);
				echo '</div>';
			}
			echo '<ul>'."\n";
			foreach ($this->quotes as $quote)
			{
				echo '<li>'."\n";
				echo $this->get_quote_html($quote);
				echo '</li>'."\n";
			}
			echo '</ul>'."\n";
			if (!empty($this->params['footer_html']) || $this->params['enable_javascript_refresh'])
			{
				echo '<div id="quotes_footer">'."\n";
				if (!empty($this->params['footer_html'])) echo ($this->params['footer_html']);
				echo '</div>';
			}
			echo '</div>';
		}
		
		function get_quote_html(&$quote)
		{
			$quote_content = $this->get_quote_content_html($quote);
			$quote_divider = $this->get_quote_divider_html($quote);
			$quote_author = $this->get_quote_author_html($quote);
			$quote_html = str_replace(array('[[quote]]', '[[divider]]', '[[author]]'), array($quote_content, $quote_divider, $quote_author), $this->params['template']);
			return $quote_html;
		}
		
		function get_quote_content_html(&$quote)
		{
			$short_description = ($this->params['prefer_short_quotes']) ? $quote->get_value('description') : '';
			$quote_text = ($short_description) ? $short_description : $quote->get_value('content');
			$quote_html = '<span class="quoteText">';
			$quote_html .= $quote_text;
			$quote_html .= '</span>';
			return $quote_html;
		}
		
		function get_quote_author_html(&$quote)
		{
			$author_html = '<span class="quoteAuthor">';
			$author_html .= $quote->get_value('author');
			$author_html .= '</span>';
			return $author_html;
		}
		
		function get_quote_divider_html(&$quote)
		{
			$divider_html = '<span class="quoteDivider">';
			$divider_html .= ($quote->get_value('author')) ? $quote->get_value('quote_divider') : '';
			$divider_html .= '</span>';
			return $divider_html;
		}
		
		/**
		 * This method will clear the quotation cache generated by this module for a site and page
		 * @todo implement something to call this
		 */
		function clear_cache($site_id = '', $page_id = '')
		{
			$site_id = ($site_id) ? $site_id : $this->site_id;
			$page_id = ($page_id) ? $page_id : $this->page_id;
			if ($site_id && $page_id)
			{
				$qh = new QuoteHelper($site_id, $page_id);
				$qh->clear_cache();
			}
			else trigger_error('clear_cache needs a site_id and page_id');			
		}
	}
?>
