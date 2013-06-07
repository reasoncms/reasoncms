<?php 

ini_set("display_errors", "on");
ini_set("memory_limit", "2048M");
/**
 * Dependencies
 */
include_once('reason_header.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('classes/plasmature/upload.php');
reason_include_once('classes/object_cache.php');
reason_include_once('scripts/import/job.php');
reason_include_once('scripts/import/jobs/basic.php');
reason_include_once('scripts/import/drupal/drupal_cleanup_job.php');
include_once(XML_PARSER_INC . 'xmlparser.php');

echo 'SiteID='.id_of('lis').'<br />';
echo 'UserID='.id_of('smitst01').'<br />';
echo 'PublicationTypeID='.id_of('publication_type').'<br />';
echo 'NewsTypeID='.id_of('news').'<br />';

$doc = get_xml();
die(        pray($doc));


/* Build the Blog */
    echo '/* Build the Blog */<br />';
        $publication_values;
        $publication_values['new'] = 0;
        $publication_values['description'] = 'Publication Description';
        $publication_values['unique_name'] = 'super_sweet';
        $publication_values['state'] = 'Live';
        $publication_values['hold_comments_for_review'] = 'yes';
        $publication_values['posts_per_page'] = 12;
        $publication_values['blog_feed_string'] = 'blog';
        $publication_values['publication_type'] = 'Blog';
        $publication_values['has_issues'] = 'no';
        $publication_values['has_sections'] = 'no';
        $publication_values['date_format'] = 'F j, Y \a\t g:i a';

    $publication_id = reason_create_entity(
                    id_of('lis'), 
                    id_of('publication_type'), 
                    id_of('smitst01'), 
                    'Steve\'s Super Sweet Publication', 
                    $publication_values
                );

/* Build a Post */
    echo '/* Build a Post */<br />';
    $post_values;
    $post_values['status'] = 'published'; 
    $post_values['release_title'] = 'I am a Title';
    $post_values['author'] = 'smitst01';
    $post_values['content'] = 'Here\'s some super_sweet content!! Here\'s some super_sweet content!!! Here\'s some super_sweet content!!!!';
    $post_values['description'] = 'Here\'s a bitch-ass description!';
    $post_values['datetime'] = date('Y-m-d H:i:s', time());
    $post_values['keywords'] = 'some keywords, yo!';
    $post_values['show_hide'] = 'show';
    $post_values['new'] = 0;

    $new_post_id = reason_create_entity( 
                id_of('lis'),  
                id_of('news'), 
                id_of('smitst01'), 
                $post_values['release_title'], 
                $post_values
            );
/* Add Categories to the Post */
    echo '/* Add Categories to the Post */ <br />';
    create_relationship( $new_post_id, $publication_id, relationship_id_of('news_to_publication') );


/* Create Publication → Post relationship */
    echo '/* Create Publication to Post relationship */<br />';
    create_relationship( $new_post_id, '465370', relationship_id_of('news_to_category') );

/**
 * Drupal wraps the post-author field in an html link,
 * strip away the link for the username
 */
// function cleanup_author( $author_link ){
//     $author_link;

//     return $username;
// }

/**
 * Drupal wraps the taxonomies in html links,
 * strip away the link for the names of the categories
 * 
 * @return array
 */
// function cleanup_categories( $taxonomy_links ){
//     $categories;

//     return $categories;
// }

function get_xml(){
    $file = '/Users/smitst01/Sites/reason.local/PublicBlogPostsShort.xml';
            $xml = file_get_contents($file);
            $xml_parser = new XMLParser($xml);
            $xml_parser->Parse();
            if (empty($xml_parser->document))
            {
                echo('The file you uploaded could not be parsed and may not be an xml file.');
            } else {
                return $xml_parser->document;
            }
}