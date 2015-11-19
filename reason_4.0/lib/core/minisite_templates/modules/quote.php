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
 * The quote module displays quotes and is backwards compatible with older versions of the quote module.
 * Now with JSON support, and pseudo random quote generation.
 *
 * If changing class names or the HTML generation structure, make sure to modify the html generation
 * portions of quote_retrieve.js so that dynamically created quotes maintain the same structure.
 * 
 * @author Tate Bosler
 */
	class QuoteModule extends DefaultMinisiteModule
	{
		
		static function setup_supported_apis()
		{
			$quote_api = new ReasonAPI(array('json', 'html'));
			self::add_api('quote', $quote_api);
		}
		
		var $quotes;
		
		/**
		 * If enable_javascript_refresh is set to true, num_to_display will be ignored (set to 1).
		 * 
		 * @todo implement multi-quote javascript refresh
		 */
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
		
		var $cleanup_rules = array('last_quote' => 'turn_into_int');
		
		function init( $args = array() )
		{	
			$qh = new QuoteHelper($this->site_id, $this->page_id);
			if ($this->params['quote_divider']) $qh->set_quote_divider($this->params['quote_divider']);
			if ($this->params['cache_lifespan'] > 0) $qh->set_cache_lifespan($this->params['cache_lifespan']);
			if ($this->params['page_category_mode']) $qh->set_page_category_mode($this->params['page_category_mode']);
			$qh->init();
			
			// javascript refresh mode currently forces display to a single quote
			$num_to_display = ($this->params['enable_javascript_refresh']) ? 1 : $this->params['num_to_display'];
			if($this->request['last_quote'] > 0) $qh->set_unavailable_quote_id($this->request['last_quote']);
			$this->quotes =& $qh->get_quotes($num_to_display, $this->params['rand_flag']);
			
			$head_items =& $this->get_head_items();
			// if enabled, add API JS to head items
			if($this->params['enable_javascript_refresh']) {
				$head_items->add_javascript(JQUERY_URL, true); // load jquery - specify load first
				$head_items->add_javascript(WEB_JAVASCRIPT_PATH . 'jquery.reasonAjax.js'); // our quote.js file requires jquery.reasonAjax.js
				$head_items->add_javascript(WEB_JAVASCRIPT_PATH . 'modules/quote/quote.js');
			}
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
			echo '<div id="quotes" class="quotes '.$this->get_api_class_string().'">';
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
		
		function run_api()
		{
			$api = $this->get_api();
			if ($api->get_name() == 'quote')
			{
				if ($api->get_content_type() == 'json') {
					$quotes = array();
					foreach($this->quotes as $quote) {
						$quotes[] = array("reasonID" => $quote->id(), "text" => $this->get_quote_content_plaintext($quote), "author" => $this->get_quote_author_plaintext($quote));
					}
					$api->set_content(json_encode(array('quotes' => $quotes)));
				}
				if ($api->get_content_type() == 'html') {
					$content = "";
					foreach($this->quotes as $quote) {
						$content .= '<p>'.$this->get_quote_html().'</p>';
					}
					$api->set_content($content);
				}
				$api->run();
			}
			else parent::run_api(); // support other apis defined by parents
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
			$quote_html = '<span class="quoteText" data-quote-id="'.$quote->id().'">';
			$quote_html .= $quote_text;
			$quote_html .= '</span>';
			return $quote_html;
		}
		
		function get_quote_content_plaintext(&$quote)
		{
			$short_description = ($this->params['prefer_short_quotes']) ? $quote->get_value('description') : '';
			$quote_text = ($short_description) ? $short_description : $quote->get_value('content');
			return $quote_text;
		}
		
		function get_quote_author_html(&$quote)
		{
			$author_html = '<span class="quoteAuthor">';
			$author_html .= $quote->get_value('author');
			$author_html .= '</span>';
			return $author_html;
		}
		
		function get_quote_author_plaintext(&$quote)
		{
			return $quote->get_value('author');
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