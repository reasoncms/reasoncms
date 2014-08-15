<?php
/**
 * Register module with Reason and include dependencies
 */
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'VolunteerOtherModule';

class VolunteerOtherModule extends DefaultMinisiteModule
{

    function init()
    {

    }

    function has_content()
    {
        return true;
    }

    function show_person( $person ) // {{{
	{
        $this->show_image($person);
        echo '<a name="'.$person['ds_username'][0].'"></a>'."\n";
        echo '<h2 class="facStaffName">'.$person['full_name'];
        echo '</h2>'."\n";


        if( !empty( $person[ 'title' ])
            || !empty( $person[ 'ds_phone' ] )
            || !empty( $person[ 'mail' ] )
            || !empty( $person['content' ] ) )
        {
            echo '<div class="facStaffInfo">'."\n";
            if ( !empty( $person['title']))
            {
                echo '<h3 class="facStaffTitle">'.$person['title'].'</h3>'."\n";
            }

            echo '<ul class="facStaffContact">';

            if ($person['edupersonaffiliation'][0] != 'Emeritus')
            {

                if ( !empty ( $person['ds_office'] ) ){
                    echo '<li class="facStaffOffice"><strong>Office:</strong> ';
                    foreach ($person['ds_office'] as $office) {
                        echo preg_replace('/;/', ', ', $office);
                    }
                    echo '</li>' . "\n";

                }
                if ( !empty ( $person['ds_phone'] )){
                    echo '<li class="facStaffPhone"><strong>Phone:</strong> ' . preg_replace('/,/', ', ', $person['ds_phone']) . '</li>' . "\n";
                }
            }

            if ( !empty ( $person['mail'] ))
            {
                echo '<li class="facStaffEmail"><strong>E-mail:</strong> <a href="mailto:' . $person['mail'] . '">' . $person['mail'] . '</a></li>' . "\n";
            }

            echo '</ul>';

            if (!empty( $person['content' ] ) )
            {
                echo '<div class="facStaffContent">' . $person[ 'content' ]  . '</div>' . "\n";
            }
            echo '</div>'."\n";
        }
    }

    function show_image( $person )
    {
        $image_id = '';
        if( !empty( $this->reason_netids[ $person[ 'ds_username' ][0] ] ) )
                $image_id = $this->grab_faculty_image( $this->reason_netids[ $person[ 'ds_username' ][0] ] );
        if (!empty($image_id))
        {
            echo "<figure class='facStaffImage'>";
    		$image = get_entity_by_id($image_id);
    		$url = WEB_PHOTOSTOCK . $image_id . '.' . $image['image_type'];
    		$thumb = WEB_PHOTOSTOCK . $image_id . '_tn.' . $image['image_type'];
    		if (!file_exists(preg_replace("|/$|", "", $_SERVER['DOCUMENT_ROOT']) . $thumb))
    		{
    			$thumb = $url;
    		}
    		$d = max($image['width'], $image['height']) / 125.0;
    		//echo '<div class="figure" style="width:' . intval($image['width']/$d) .'px;">';
    		echo '<a href="'. $url . '" class="highslide" onclick="return hs.expand(this, imageOptions)">';
            echo '<img src="' . $thumb . '" border="0" alt="" title="Click to enlarge" />';
            echo '</a>';

            //show_image( $image_id, false,true,false );
            //echo "</div>\n";
            echo "</figure>\n";
        }
    }
}
?>