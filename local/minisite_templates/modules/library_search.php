<?php
reason_include_once( 'minisite_templates/modules/default.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LibrarySearchModule';

class LibrarySearchModule extends DefaultMinisiteModule {
    function init( $args = array() ) {

    }

    function has_content() {
        return true;
    }

    function run() {
?>
        <i>(Redirects to 'http://books.luther.edu/airpac/search')</i>
        <p style="margin-top:5px;">Search the Preus Library Catalog</p>
        <p></p>
        <form method="post" action="http://books.luther.edu/airpac/search">
            <select name="searchtype" style="margin-bottom:5px;" title="Search Type">
                <option value="a" >AUTHOR

                <option value="t" >TITLE

                <option value="d" >SUBJECT

                <option value="X" >KEYWORD

                <option value="c" >LC CALL NO
            </select>
            <br>
            <input style="margin-bottom:5px;" type="text" name="searchstring" size="30" maxlength="75"

                   value=""			
                   >
            <input type="hidden" name="origsearchstring" value="X">
            <br>

            <select style="margin-bottom:5px;" name="scope" title="Scope" value="9">
                <option value="9" selected>View Entire Collection

                <option value="1" >Reference

                <option value="2" >Curriculum

                <option value="3" >Sound recordings

                <option value="4" >DVD/Video

                <option value="5" >Music scores

                <option value="6" >Current periodicals

                <option value="7" >eBooks

                <option value="8" >Databases
            </select>
            <br>	            		                      
            <input type="submit" value="Search">              
            <input type="hidden" name="sourcebrowse" value="welcome">
            <input type="hidden" name="action" value="search">			
        </form>                                                                        			
        <a href="http://books.luther.edu/airpac/search?action=LoadAVSSearchPageAction">Advanced Searching</a><br />
        <a href="http://books.luther.edu/airpac/patron?action=GetPatronInfoAction">View your patron information</a>
        <p></p>
        <a href="http://books.luther.edu/airpac/jsp/help/defaultPhraseSearchHelp.jsp">Need help searching?</a>
        <!--<div id="help">
            <p>Complete the form above and click 'Search'.</p>
            <p style="margin:0;padding:0;">For example, select 'Author'</p>
            <p style="margin:0;padding:0;">Enter 'shakespeare, william'</p>
            <p style="margin:0;padding:0;">Limit collection to 'eBooks'</p>
        </div>-->
<?php
    }
}
?>
