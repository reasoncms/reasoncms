<?php
/**
 * @package carl_util
 * @subpackage pdf
 */

/**
 * Include all needed functions
 */
include_once('paths.php');
include_once( CARL_UTIL_INC . 'pdf/htmlparser.inc' );
include_once( CARL_UTIL_INC . 'tidy/tidy.php' );

/**
 * A class that takes HTML and renders it as a PDF
 *
 * This class requires pdflib
 *
 * See http://us3.php.net/pdf
 */
class PDFDoc
{
    var $fonts = array( 'p' => array( 'name' => 'Times-Roman', 'size' => 10 ),
                        'h1' => array( 'name' => 'Times-Roman', 'size' => 18 ),
                        'h2' => array( 'name' => 'Times-Roman', 'size' => 16 ),
                        'h3' => array( 'name' => 'Times-Roman', 'size' => 14 ),
                        'em_p' => array( 'name' => 'Times-Italic', 'size' => 10 ),
                        'strong_p' => array( 'name' => 'Times-Bold', 'size' => 10),
                        'strong_em_p' => array( 'name' => 'Times-BoldItalic', 'size' => 10),
                        'em_h3' => array( 'name' => 'Times-Italic', 'size' => 14),
                        'dingbat' => array( 'name' => 'ZapfDingbats', 'size' => 10)
                        );

    var $tags = array(  'p' => false,
                        'h1' => false,
                        'h2' => false,
                        'h3' => false,
                        'em' => false,
                        'strong' => false,
                        'ul' => false,
                        'ol' => false,
                        'li' => false,
                        'a' => false,
                        'br' => false
                        );

	var $page = array(  'num' => 1,
                        'tabspace' => 40,
                        'width' => 595,
                        'height' => 842,
                        'margin_l' => 50,
                        'margin_r' => 50,
                        'margin_top' => 50,
                        'margin_bottom' => 50, 
                        'print_width' => 495, //495
                        'current_width' => 495,
                        'print_height' => 742, 
                        'vert_space' => 30
                        );
    var $debug = array( 'show_text_boxes' => false,
                        'show_undefined_tags' => false
                        );
                    
    var $title;
    var $author;
    var $teaser;
    var $story;
    var $pdf;
    var $cur_font;
    var $list_item_num;
    var $br_continue;
    var $page_close_flag;
    var $next_text_box_y;
    var $next_text_box_x;
    var $previous_text_box;
    var $p_tag_interrupted;
    var $font_handle;
   
    //Constructor
    function PDFDoc( $story_info )
    {   
            $this->title = $story_info['title'];
            $this->author = $story_info['author'];
            $this->teaser = $story_info['teaser'];
            $this->story = $story_info['story'];
    }
    
    function BeginPDF()
    {
        if( !empty( $this->story ))       
        {
            $this->story = utf8_decode( $this->story );
            $this->story = tidy( $this->story );

            $this->pdf = pdf_new();
            PDF_set_parameter($this->pdf , 'licensefile', $_SERVER['PDFLIBLICENSEFILE']);
            PDF_begin_document($this->pdf , "", "");
            #pdf_open_file($this->pdf);
            pdf_set_border_style( $this->pdf, 'solid', 0 );
            //pdf_set_parameter( $this->pdf, 'textformat', 'utf8' );
            if( !empty( $this->author ) )
                pdf_set_info($this->pdf, "Author", $this->author);
            
            if( !empty( $this->teaser ) )
                pdf_set_info($this->pdf, "Subject", $this->teaser);
        
            pdf_set_info($this->pdf, "Creator", "College Relations PDF to HTML converter" );
            pdf_begin_page($this->pdf, $this->page['width'], $this->page['height']);
            pdf_add_bookmark($this->pdf, 'Page '.$this->page['num'] , 0 , 0);
            $this->page_close_flag = false;
            $this->p_tag_interrupted = false;
            pdf_set_text_pos( $this->pdf, $this->page['margin_l'], $this->page['height'] - $this->page['margin_top'] );
            
            if( !empty( $this->title ) )
            {
                pdf_set_info($this->pdf, "Title", $this->title);
                $this->show_title();
            }
            //$this->previous_text_box = pdf_get_value( $this->pdf, 'texty');
            $this->my_set_font( 'p' );
            $this->ShowStory();
            $this->EndStory();
        }
    }
    
    function ShowStory()
    {
        $this->my_set_font( 'p' );
        //Parse the story
        $parser = new htmlparser( $this->story );
        while( $parser->parse() )
        {
            //calls the appropriate function to setup the current tag for display
            //if we are displaying text, then we need to pass along the text. 
            $tag_function = 'init_'.strtolower( $parser->iNodeName );

            if( $tag_function == 'init_text' )
                $this->$tag_function( $parser->iNodeValue );
            elseif( method_exists( $this, $tag_function ) )
                $this->$tag_function();
            elseif( $this->debug['show_undefined_tags'] )
                $this->pdf_print_txt( $parser->iNodeName );
        }
    }
    
    function EndStory()
    {
        pdf_end_page($this->pdf);
        pdf_close($this->pdf);
        $buffer = PDF_get_buffer($this->pdf); 

        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-type: application/pdf');
        header('Content-Length: '.strlen($buffer));
        $filename = str_replace( ' ', '_', $this->title );
        header('Content-Disposition: inline; filename='.$filename.'.pdf');
        header('Content-Transfer-Encoding: binary');
        
        echo $buffer;
        
        pdf_delete($this->pdf);
    }

    function show_title()
    {
        $this->my_set_font( 'h1' );
        $this->toggle( 'h1' );
        $this->pdf_print_txt( $this->title );
        $this->toggle( 'h1' );
        $this->my_set_font( 'em_p' );
        if( !empty( $this->author ) )
            $this->pdf_print_txt( 'by '.$this->author );
        $this->new_line();
        $this->new_line();
    }
    
    function new_line()
    {
        pdf_set_text_pos( $this->pdf, $this->page['margin_l'], pdf_get_value( $this->pdf, 'texty', 0 ) - pdf_get_value( $this->pdf, 'leading' , 0 ) );
    }
    
    //Here we set a font for a PDF. Basically, we just append a string for 'active' 
    //mod tags (like <strong> and <em>) and then reference into the $this->fonts array
    function my_set_font( $tag )
    {
        $prefix = '';
        
        if( $this->tags['strong'] )
            $prefix .= 'strong_';
        
        if( $this->tags['em'] )
            $prefix .= 'em_';
        
        $font = PDF_findfont($this->pdf, $this->fonts[$prefix.$tag]['name'], "builtin", 0); 
        $this->cur_font = $tag;
        $this->font_handle = $font;
        
        pdf_setfont( $this->pdf, $font, $this->fonts[$tag]['size'] );
    }
    
    //A simple bool toggle function for the $this->tags array
     
    function toggle( $var )
    {
        if( $this->tags[$var] )
            $this->tags[$var] = false;
        else
            $this->tags[$var] = true;
            
    }

    //wrapper for pdf_show_boxed
    function show_text_box( $pdf, $txt, $x, $y, $w, $h, $j )
    {
        //if there are some spaces at the begining of the text, 
        //we want to eliminate them and have there only be one. 
        //Basically a hack used to fix an annoying problem. 
        if( preg_match( '/^(\s)+/', $txt) )
            $txt = ' '.ltrim( $txt );
        $overflow = pdf_show_boxed( $pdf, $txt, $x, $y, $w, $h, $j , "" );
        if( $this->debug['show_text_boxes'] )
        {
            pdf_rect( $pdf, $x, $y, $w, $h );
            pdf_stroke( $pdf );
        }
        
        $this->previous_text_box = $y;
        return $overflow;
    }

    //used to setup new text boxes. 
    function set_new_text_box( $x, $y )
    {
        $offset = 0;
        //if we are in a list, we need some tabs
        if( $this->tags['ul'] || $this->tags['ol'] )
            $offset = $this->page['tabspace'];
        
        $this->page['current_width'] = $this->page['print_width'] - $offset;
        $this->next_text_box_x = $x + $offset;
        $this->next_text_box_y = $y;
        pdf_set_text_pos( $this->pdf, $x + $offset, $y );
    }
    
    
    //Takes link text and the URL of a link and inserts a hyperlink into the PDF.
    //This function should be called after the text of the link has been displayed in the PDF.
    function set_web_link( $link_text, $URL )
    {
        //here, we assume that the text of the link has been displayed and that our current
        //x and y coordinates reflect that. We subtract the length of the string to determine
        //the lower-left hand corner. 
        $llx = pdf_get_value( $this->pdf, 'textx' , 0) - pdf_stringwidth( $this->pdf, $link_text , $this->font_handle  ,  $this->fonts[$this->cur_font]['size'] );
        $lly = pdf_get_value( $this->pdf, 'texty' , 0);
        $urx = pdf_get_value( $this->pdf, 'textx' , 0 );
        $ury = $lly + pdf_get_value( $this->pdf, 'fontsize' , 0 );

        //we run a regular expression here to try and see if we have a URL. If we don't have an URL
        //then we search google for the <a> tag's text
        //This can probably be improved on
        //the url doesn't have 'http://' at the begining of it, we need to add it.
        $domain_match = '\.(net|com|gov|edu|org|mil|int|firm|shop|web|arts|rec|info|nom)';
        if( !preg_match( "/$domain_match/", $URL ) )
        {   
            $URL = 'http://www.google.com/search?&q='.str_replace( ' ', '+', $URL );
        }
        elseif( preg_match( "/(.)*@(.)*$domain_match/", $URL ) )
        {
            $URL = 'mailto:'.$URL;
        }
        elseif( substr( $URL, 0, 7 ) != 'http://' )
        {
            $URL = 'http://'.$URL;
        }
        
        pdf_add_weblink( $this->pdf, $llx, $lly, $urx, $ury, $URL );
    }

    //This function prints the overflow from text boxes
    function show_text_box_overflow( $txt )
    {
        $fontsize = pdf_get_value( $this->pdf, 'fontsize' ,0 );
        $length = pdf_stringwidth( $this->pdf, $txt , $this->font_handle  ,  $this->fonts[$this->cur_font]['size'] );
           
        $textHeight = ceil( $length/$this->page['print_width'] )*$fontsize;
        $floor = $this->page['margin_bottom'];
        $current_y = pdf_get_value( $this->pdf, 'texty' ,0 );
        $overflow = 0;

        if( trim( $txt ) != '' )
        {
            //if our text is going to run past the floor, we need to print as much as we can
            //and then start a new page to print the rest. Otherwise, we just print the text
            //We are using a variable for a function name later in the code.
            if( $current_y - $textHeight < $floor )
            {
                $textHeight = $current_y - $floor;
                $overflow_function = 'show_page_overflow';
            }
            else
            {
                $overflow_function = 'show_text_box_overflow';
            }
        
            $this->set_new_text_box( $this->page['margin_l'], $current_y ); 
            $overflow = $this->show_text_box($this->pdf, $txt, $this->next_text_box_x, $this->next_text_box_y - $textHeight, $this->page['current_width'], $textHeight,'left');

            if( $this->tags['a'] )
                $this->set_web_link( $txt, $txt );

            if( $overflow > 0 )
            {
                //call the appropriate function
				$otxt = substr($txt, 0 - $overflow);
				if( trim( $otxt ) != '' )
					$this->$overflow_function( $otxt );
			}
        }
    }
    
    //sets up a new page and text box
    function show_page_overflow( $txt )
    {
        $this->page['num']++;
        $this->start_page( 'Page '.$this->page['num'] );
        $this->set_new_text_box( $this->page['margin_l'], $this->page['height'] - $this->page['margin_top'] );
        $this->show_text_box_overflow( $txt );
    }
        
    
    function pdf_print_txt( $txt )
    {
        //if we are still in a p, then our next text box coordinates are the current location
        //and we set the width to equal the rest of the line, output to the end of the line and then 
        //continue with the output as normal. 
        if( $this->p_tag_interrupted )
        {       
            $fontsize = pdf_get_value($this->pdf,'fontsize' , 0);
            $length = pdf_stringwidth($this->pdf, $txt , $this->font_handle  ,   $this->fonts[$this->cur_font]['size'] ); 
            $textHeight = ceil($length/$this->page['print_width'])*$fontsize;
            $max_w = $this->page['margin_l'] + $this->page['current_width'] - pdf_get_value( $this->pdf, 'textx', 0 ); 
            
            $this->set_new_text_box( pdf_get_value( $this->pdf, 'textx' , 0), pdf_get_value( $this->pdf, 'texty' , 0) );

            //if the length of our string is greater than the remaining length of the line
            //then we set up an overflow case. Otherwise, we just set up the textbox to be the
            //size of whatever it is that we are enclosing. 
            
            if( $length >= $max_w )
            {   
                $overflow = 0;
                if( trim( $txt ) != '' ) 
                {
                    $overflow = $this->show_text_box( $this->pdf, $txt, $this->next_text_box_x, $this->next_text_box_y, $max_w, $fontsize, 'left' ); 
                    if( $overflow > 0 )
                    {
                        $this->show_text_box_overflow( substr( $txt, 0 - $overflow ) ); 
                    }
                }
            }
            else
            {
                if( pdf_get_value( $this->pdf, 'texty' , 0) - $textHeight < $this->page['margin_bottom'] + $fontsize  )
                {
                    $this->show_page_overflow( $txt ); 
    
                    if( $this->tags['a'] )
                        $this->set_web_link( $txt, $txt ); 
                }
                elseif( trim( $txt ) != '' ) 
                {
                    $this->show_text_box( $this->pdf, $txt , $this->next_text_box_x, $this->next_text_box_y, 0, 0, 'left' ); //$this->txt

                    if( $this->tags['a'] )
                        $this->set_web_link( $txt, $txt ); 
                }
            }
        }
        else
        {
            //Here, we are not interrupting a current <p> block. 
            if( trim( $txt ) != '' ) 
            {
                $this->show_text_box_overflow( $txt ); 
            }
              
        }

        return true;
        
    }

    function start_page( $bookmark )
    {
        //there is a page still open, must close it first
        if (!$this->page_close_flag)
            pdf_end_page($this->pdf);
    
        pdf_begin_page($this->pdf, $this->page['width'], $this->page['height']);
        pdf_add_bookmark($this->pdf, $bookmark , 0 , 0);
    
        //initialize font
        $this->my_set_font( $this->cur_font );
        //false means there is a page open
        $this->page_close_flag = false;
        
    }

    function init_text( $txt )
    {
        $this->my_set_font( $this->cur_font );
        $this->pdf_print_txt( $txt ); 
    }
    function init_p()
    {
        $this->my_set_font( 'p' );
        $this->toggle( 'p' );
        
        if( !$this->tags['p'] )
        {
            $this->new_line();
            
            if( $this->p_tag_interrupted )
            {
                $this->p_tag_interrupted = false;
            }
            $this->new_line();
        }
    }
    function init_br()
    {
        if( $this->tags['ol'] || $this->tags['ul'] )
            $this->br_continue = true;
        else
            $this->new_line();
        
        if( $this->tags['p'] )
            $this->p_tag_interrupted = true;
    }
    function init_em()
    {
        $this->toggle( 'em' );
        if( $this->tags['p'] )
            $this->p_tag_interrupted = true;
        
    }
    function init_h3()
    {
        $this->toggle( 'h3' );
        if( !$this->tags['h3'] )
            $this->new_line();
            
        $this->my_set_font( 'h3' );
    }
    function init_h2()
    {
        $this->toggle( 'h2' );
        if( !$this->tags['h2'] )
            $this->new_line();
            
        $this->my_set_font( 'h2' );
    }                
    function init_h1()
    {
        $this->toggle( 'h1' );
        if( !$this->tags['h1'] )
            $this->new_line();
            
        $this->my_set_font( 'h1' );
    }
    function init_strong()
    {
        $this->toggle( 'strong' );
        if( $this->tags['p'] )
            $this->p_tag_interrupted = true;
    }
    function init_ul()
    {
        $this->toggle( 'ul' );
        if( !$this->tags['ul'] )
            $this->new_line();
    }
    function init_ol()
    {
        $this->toggle( 'ol' );
        //If we are at the end of an ordered list, reset the item counter
        //otherwise we are starting an ordered list.
        if( !$this->tags['ol'] )
        {
            $this->list_item_num = 0;    
            $this->new_line();
            $this->new_line();
        }
        else
            $this->list_item_num = 1;
    }
    function init_li()
    {
        //If we are at the end of a list item, reset br_continue
        $this->toggle( 'li' );
        if( !$this->tags['li'] )
            $this->br_continue = false;
        
        $font = $this->cur_font;
        
		//If we are displaying an unordered list, then we want to capture the current font
		//set up a list delimiter, print out the delimeter and reset the x and y coordinates
		//so the ensuing text box displays correctly
		if( $this->tags['li'] ) //$this->tags['ul'] && 
        {
            if( $this->tags['ul'] )
            {
                $listdelim = 'v'; //l
                $this->my_set_font( 'dingbat' );
            }
            elseif( $this->tags['ol'] )
            {
                $listdelim = $this->list_item_num.')';
                $this->list_item_num++;
            }
                
            if( pdf_get_value( $this->pdf, 'texty' ,0 ) - pdf_get_value( $this->pdf, 'fontsize' , 0) < $this->page['margin_bottom'] )
            {
                $this->page['num']++;
                $this->start_page( 'Page '.$this->page['num'] );
                pdf_set_text_pos( $this->pdf, $this->page['margin_l'], $this->page['height'] - $this->page['margin_top'] );
            }
            $old_x = pdf_get_value( $this->pdf, 'textx' , 0);
            $old_y = pdf_get_value( $this->pdf, 'texty' , 0 );
            $width = pdf_stringwidth( $this->pdf, $listdelim , $this->font_handle ,   $this->fonts[$this->cur_font]['size'] );
            pdf_show_xy( $this->pdf, $listdelim, $this->page['margin_l'] + $this->page['tabspace'] - $width, pdf_get_value( $this->pdf, 'texty' , 0)-pdf_get_value( $this->pdf, 'leading' , 0) ); //pdf_get_value( $this->pdf, 'descender', $this->font_handle) 
            pdf_set_text_pos( $this->pdf, $old_x, $old_y );
			$this->my_set_font( $font );
        }
    }
    function init_a()
    {
        $this->toggle( 'a' );
        $this->my_set_font( 'p' );
        if( $this->tags['p'] )
            $this->p_tag_interrupted = true;
    }
}
?>
