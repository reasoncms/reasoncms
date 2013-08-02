<?php 

ini_set("display_errors", "on");
ini_set("memory_limit", "2048M");
/**
 * Dependencies
 */
include_once('reason_header.php');
connectDB( REASON_DB );
reason_include_once( 'function_libraries/user_functions.php' );
force_secure_if_available();
$current_user = check_authentication();
$reason_user_id = get_user_id ( $current_user );
if (!reason_user_has_privs( $reason_user_id, 'view_sensitive_data' ) )
{
    die('<h1>Sorry.</h1><p>You do not have proper permissions to run this script.</p></body></html>');
} else {
    echo "Go!<hr>";
}
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('classes/plasmature/upload.php');
reason_include_once('classes/object_cache.php');
reason_include_once('scripts/import/job.php');
reason_include_once('scripts/import/jobs/basic.php');
reason_include_once('scripts/import/drupal/drupal_cleanup_job.php');
include_once(XML_PARSER_INC . 'xmlparser.php');

echo '<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />'."\n";
$doc = get_xml();
// foreach ($doc as $node) {
//     pray($node->title);
//     echo $node->title[0]->tagData.'<hr>';
// }
// die();
// cleanup_author($doc->node[1]->post_author[0]->tagData);


/* Build the Blog */
    $publication_values;
    $publication_values['new'] = 0;
    $publication_values['description'] = 'Publication Description';
    $publication_values['unique_name'] = 'lis_blog';
    $publication_values['state'] = 'Live';
    $publication_values['hold_comments_for_review'] = 'yes';
    $publication_values['posts_per_page'] = 12;
    $publication_values['blog_feed_string'] = 'blog';
    $publication_values['publication_type'] = 'Blog';
    $publication_values['has_issues'] = 'no';
    $publication_values['has_sections'] = 'no';
    $publication_values['date_format'] = 'F j, Y \a\t g:i a';

    if (!reason_unique_name_exists($publication_values['unique_name'])){
        echo '/* Build the Blog */<hr>';
        $publication_id = reason_create_entity(
                        id_of('lis'), 
                        id_of('publication_type'), 
                        id_of('smitst01'), 
                        'Steve\'s Super Sweet Publication', 
                        $publication_values
                    );
    } else { 
        echo '/* Blog already exists */<hr>';
        $publication_id = id_of($publication_values['unique_name']);
    }

/* Build a Post */
    echo '/* Build a Post */<hr>';
    $post_values;
    foreach ($doc as $node) {
        $post_values['status'] = 'published'; 
        $post_values['release_title'] = $node->title[0]->tagData;
        $post_values['author'] = cleanup_author($node->post_author[0]->tagData);
        $post_values['content'] = $node->body[0]->tagData;
        $description = cleanup_description($node->body[0]->tagData, $post_values['release_title'] );
        $post_values['description'] = $description;
        $post_values['datetime'] = cleanup_date($node->post_date[0]->tagData);
        $post_values['show_hide'] = 'show';
        $post_values['new'] = 0;

        $new_post_id = reason_create_entity( 
            id_of('lis'),  
            id_of('news'), 
            id_of('smitst01'), 
            $post_values['release_title'], 
            $post_values
        );

        /* Create Publication → Post relationship */
        echo '/* Creating Publication → Post relationship for '.$new_post_id.' */ <hr>';
        create_relationship( 
            $new_post_id, 
            $publication_id, 
            relationship_id_of('news_to_publication') 
        );

        pray($post_values);
        echo "<hr>";
    }

/**
 * Drupal wraps the post-author field in an html link,
 * strip away the link for the username
 */
function cleanup_author( $author_link ){
    $author_link = preg_replace('/<a href="\/user\/\d+" title="View user profile.">/', '', $author_link);
    $username = preg_replace('/<\/a>/', '', $author_link);

    return $username;
}

function cleanup_date( $drupal_date ){
    $date_pieces = explode(' - ', $drupal_date);
    $date = new DateTime($date_pieces[0]);
    $time_pieces = explode(':', $date_pieces[1]);
    if (substr($time_pieces[1], -2) == 'pm'){
        $hour = $time_pieces[0] + 12;
    } else {
        $hour = $time_pieces[0];
    }
    $minutes = substr($time_pieces[1], 0, 2);
    $date->setTime($hour, $minutes);
    
    return $date->format('Y-m-d H:i:s');
}

function cleanup_description( $description, $title ){
    $desc = substr($description, 0, 200);
    $desc = preg_replace('/<a href=\"(.*?)\">/', "\\2", $desc);
    $desc = preg_replace('/<\/a>/', '', $desc);

    if (stripos($title, 'User Services Weekly') !== false)
        $desc = '';
    $desc = strip_tags($desc);

    return $desc;
}

function get_xml(){
    $file = '/Users/smitst01/Sites/reason.local/PublicBlogPostsShort.xml';
    $file = '/var/reason/htdoc/LISBlogexport.xml';

    $xml = file_get_contents($file);

    if (!$xml)
        die('Could not get the file<br />');

    $xml_parser = new XMLParser($xml);
    $xml_parser->Parse();

    if (empty($xml_parser->document))
    {
        die('The file you uploaded could not be parsed and may not be an xml file.<br />');
    } else {
        return $xml_parser->document->node;
    }
}

//Account for pdfs ??

//Account for images ??