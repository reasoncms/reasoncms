<?php
/**
 * @package carl_util
 * @subpackage pdf
 */
/**
 * Merge a set of PDFs
 *
 * Merge multiple PDF documents with the output PDF having as version number
 * the maximum of the PDF version numbers of all imported documents.
 * 
 * Only the pages from the imported documents are merged; interactive elements
 * will be ignored.
 *
 * Note that you need PDFlib with pecl version 2.1 or greater to get the "smart" pdf version feature
 *
 * Example:
 *
 * <code>
 * $pdf_files = array( "pdfs/campus_visit_bingo.pdf", "pdfs/campus_visit_bingo_2.pdf", );
 * $metadata = array( 'Creator' => 'This is the creator name','Title'=>'This is the document title','Author'=>'This is the document author','Subject'=>'This is the document subject/description' );
 * if(carl_send_pdf(carl_merge_pdfs($pdf_files, $metadata), 'test.pdf'))
 * 		die();
 * else
 * 		echo 'It didn\'t work...';
 * </code>
 *
 * @param array $pdffiles File paths
 * @param array $metadata Document metadata to embed in the doc
 * @return binary pdf data
 *
 * @todo Merge document metadata too?
 */
function carl_merge_pdfs($pdffiles, $titles = array(), $metadata = array(), $metadata_encoding = 'UTF-8')
{
	if(gettype($pdffiles) != 'array')
	{
		trigger_error('$pdffiles must be an array');
		return false;
	}
	
	if(!function_exists('PDF_new'))
	{
		trigger_error('You must have PDFlib installed in order to run carl_merge_pdfs()');
		return false;
	}
	
	if(empty($pdffiles))
		return NULL;
	
	$i = 0;
	$indoc = 0;
	$pdfver = '1.0';
	$maxpdfver = '1.0';
	
	$p = PDF_new();
	
	if(defined('PDFLIB_LICENSE_KEY_FILE'))
		PDF_set_parameter($p , 'licensefile', PDFLIB_LICENSE_KEY_FILE);
	else
		trigger_error('Please define the constant PDFLIB_LICENSE_KEY_FILE with the filesystem location of your PDFlib license keys.');
	
	/* This means we must check return values of load_font() etc. */
	//PDF_set_parameter($p, 'errorpolicy', 'return');
	
	/* -----------------------------------------------------------------
	 * Loop over all input documents to retrieve the highest PDF version
	 * used
	 * -----------------------------------------------------------------
	 */
	if(function_exists('PDF_pcos_get_number'))
	{
		foreach($pdffiles as $pdffile)
		{
			/* Open the input PDF */
			if(function_exists('PDF_open_pdi_document'))
				$indoc = PDF_open_pdi_document($p, $pdffile, ''); // post-pecl 2.1
			else
				$indoc = PDF_open_pdi($p, $pdffile, '', 0); // pre-pecl 2.1
			if ($indoc < 1) {
				trigger_error('Error: '.PDF_get_errmsg($p) );
				continue;
			}
			//echo 'indoc:'.$indoc.' ';
		
			/* Retrieve the PDF version of the current document. If it is higher 
			 * than the maximum version retrieved until now make it to be the
			 * maximum version.
			 */ 
			$pdfver = PDF_pcos_get_number($p, $indoc, 'pdfversion')/10;
			if ($pdfver > $maxpdfver)
			{
				$maxpdfver = $pdfver;
			}
			
			/* Close the input document.
			 * Depending on the number of PDFs and memory strategy, PDI handles
			 * to all documents could also be kept open between the first and
			 * second run (requires more memory, but runs faster). We close all
			 * PDFs after checking the version number, and reopen them in the
			 * second loop (requires less memory, but is slower).
			 */
			if(function_exists('PDF_close_pdi_document'))
				PDF_close_pdi_document($p, $indoc); // post-pecl 2.1
			else
				PDF_close_pdi( $p, $indoc  ); // pre-pecl 2.1
		}
	}
	//echo '3 ';
	
	/* ---------------------------------------------------------------
	 * Open the output document with the maximum PDF version retrieved
	 * --------------------------------------------------------------- 
	 */
	if($maxpdfver > '1.0')
		$optlist = 'compatibility=' . $maxpdfver;
	else
		$optlist = '';
	
	//die($optlist);
		 
	if (PDF_begin_document($p, '', $optlist) == -1)
		trigger_error('Error: ' . PDF_get_errmsg($p));
		
	foreach($metadata as $key=>$value)
	{
		PDF_set_info($p, $key, $value);
	}
	
	//PDF_set_info($p, 'Creator', 'Test Creator');
	//PDF_set_info($p, 'Title', $title . ' $Revision: 1.1 $');
	
	//echo '4 ';
	
	/* --------------------------------------------------------------------
	 * Loop over all input documents to merge them into the output document       * used
	 * --------------------------------------------------------------------
	 */
	foreach($pdffiles as $pdffile)
	{
		$endpage = $pageno = $page = 0;
	
		/* Open the input PDF */
		if(function_exists('PDF_open_pdi_document'))
			$indoc = PDF_open_pdi_document($p, $pdffile, ''); // post-pecl 2.1
		else
			$indoc = PDF_open_pdi($p, $pdffile, '', 0); // pre-pecl 2.1
		if ($indoc < 1) {
			trigger_error('Error: ' . PDF_get_errmsg($p));
			continue;
		}
	
		if(function_exists('PDF_pcos_get_number'))
			$endpage = (integer) PDF_pcos_get_number($p, $indoc, '/Root/Pages/Count'); // post-pecl 2.1
		else
			$endpage = (integer) PDF_get_pdi_value( $p, '/Root/Pages/Count', $indoc, 0, 0 ); // pre-pecl 2.1
	
		/* Loop over all pages of the input document */
		for ($pageno = 1; $pageno <= $endpage; $pageno++)
		{
			$page = PDF_open_pdi_page($p, $indoc, $pageno, '');
	
			if ($page == -1) {
					trigger_error('Error: ' . PDF_get_errmsg($p));
					continue;
			}
			/* Dummy page size; will be adjusted later */
			PDF_begin_page_ext($p, 10, 10, '');
	
			/* Create a bookmark with the file name */
			if ($pageno == 1)
			{
				if(isset($titles[$pdffile]))
					$bookmark = pack('H*','feff').mb_convert_encoding($titles[$pdffile], 'UTF-16', $metadata_encoding);
				else
					$bookmark = pack('H*','feff').mb_convert_encoding($pdffile, 'UTF-16', $metadata_encoding);
				/* if(isset($titles[$pdffile]))
					$bookmark = $titles[$pdffile];
				else
					$bookmark = $pdffile; */
				PDF_create_bookmark($p, $bookmark, '');
			}
	
			/* Place the imported page on the output page, and
			 * adjust the page size
			 */
			PDF_fit_pdi_page($p, $page, 0, 0, 'adjustpage');
			PDF_close_pdi_page($p, $page);
	
			PDF_end_page_ext($p, '');
		}
		/* Close the input document */
		if(function_exists('PDF_close_pdi_document'))
			PDF_close_pdi_document($p, $indoc); // post-pecl 2.1
		else
			PDF_close_pdi( $p, $indoc  ); // pre-pecl 2.1
	}
	//echo '5 ';
	
	PDF_end_document($p, '');
	
	$buffer = PDF_get_buffer($p);
	
	pdf_delete($p);
	
	return $buffer;
}
/**
 * Send a PDF with proper headers, etc.
 *
 * @todo check to see if this is in fact the proper way to do the headers!
 * @param binary $pdf_data
 * @param string $filename
 * @return boolean success
 */
function carl_send_pdf($pdf_data, $filename, $allow_caching = false, $disposition = 'attachment')
{
	if(empty($pdf_data))
	{
		trigger_error('No pdf data provided');
		return false;
	}
	if(empty($filename))
	{
		trigger_error('No filename provided');
		return false;
	}
	if($allow_caching)
	{
		header('Pragma: public');
		header('Cache-Control: public');
	}
	else
	{
		header('Pragma: no-cache');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	}
	header('Content-type: application/pdf');
	header('Content-Length: '.strlen($pdf_data));
	header('Content-Disposition: '.$disposition.'; filename='.$filename);
	header('Content-Transfer-Encoding: binary');
	
	echo $pdf_data;
	return true;
}
?>