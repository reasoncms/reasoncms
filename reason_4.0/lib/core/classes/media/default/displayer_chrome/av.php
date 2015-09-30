<?php

reason_include_once( 'classes/media/default/media_work_displayer.php' );
reason_include_once('classes/media/interfaces/displayer_chrome_interface.php');

/**
 * The display chrome is used by the av module.  It uses the parameters unique to the AV module
 * to display the correct media file.
 *
 * @author Marcus Huderle
 */
class DefaultAVDisplayerChrome implements DisplayerChromeInterface
{
	protected $displayer;
	protected $media_work;
	protected $av_module;
	protected $request;
	
	public function set_media_work($media_work)
	{
		$this->media_work = $media_work;
		$this->displayer = new DefaultMediaWorkDisplayer();
		$this->displayer->set_media_work($media_work);
	}
	
	public function set_module($av)
	{
		$this->av_module = $av;
		$this->request = $av->request;
	}
	
	public function set_head_items($head_items)
	{}
	
	/**
	* Returns the html markup to display the media work according the av module specifications.
	* @return string
	*/
	public function get_html_markup()
	{
		$av_files = $this->displayer->get_media_files();
		
		$markup = '';
		$av_file_count = count($av_files);
		if ($av_file_count > 0)
		{
			$prev_format = '';
			$markup .= '<div class="avFiles">'."\n";
			$first_list = true;
			$query_args = array();
			if(!empty($this->request['show_transcript']))
			{
				$query_args['show_transcript'] = 'true';
			}
			foreach( $av_files as $av_file )
			{
				if($prev_format != $av_file->get_value( 'media_format' ) )
				{
					if(!$first_list)
					{
						$markup .= '</ul>'."\n";
					}
					else
					{
						$first_list = false;
					}
					$markup .= '<ul>'."\n";
				}
				if($av_file_count == 1 || (!empty($this->av_module->request['av_file_id']) && $this->av_module->request['av_file_id'] == $av_file->id() ) )
				{
					$is_current = true;
					$attrs = ' class="current"';
				}
				else
				{
					$is_current = false;
					$attrs = '';
				}
				$markup .= '<li'.$attrs.'>';
				if($is_current)
				{
					$markup .= '<strong>';
				}
				elseif($av_file->get_value( 'url' ))
				{
					$args = $query_args + array('av_file_id'=>$av_file->id());
					$markup .= '<a href="'.$this->av_module->construct_link($this->media_work,$args).'" title="'.$av_file->get_value( 'media_format' )." ".$av_file->get_value( 'av_type' ).': '.htmlspecialchars($this->media_work->get_value('name')).'" class="fileLink">';
				}
				$file_desc = '';
				if ( $av_file->get_value( 'av_part_number' ) )
				{
					$file_desc .= 'Part '.$av_file->get_value( 'av_part_number' );
					if( $av_file->get_value( 'av_part_total' ) )
					{
						$file_desc .= ' of '.$av_file->get_value( 'av_part_total' );
					}
					$file_desc .= ': ';
				}
				if( $av_file->get_value( 'description' ) )
				{
					$file_desc .= $av_file->get_value( 'description' ).': ';
				}
				if(!empty($this->media_format_overrides[$av_file->get_value( 'media_format' ) ] ) )
				{
					$file_desc .= $this->av_module->media_format_overrides[$av_file->get_value( 'media_format' ) ];
				}
				else
				{
					$file_desc .= $av_file->get_value( 'media_format' );
				}
				$file_desc .= ' '.$av_file->get_value( 'av_type' );
				$markup .= $file_desc;
				if($is_current)
				{
					$markup .= '</strong>';
				}
				elseif($av_file->get_value( 'url' ))
				{
					$markup .= "</a>";
				}
				if ( $av_file->get_value( 'media_size' ) || $av_file->get_value( 'media_duration' ) || $av_file->get_value( 'media_quality' ) )
				{
					$markup .= " <span class='smallText'>(";
					$xtra_info = array();
					if ( $av_file->get_value( 'media_size' ) )
					{
						$xtra_info[] = $av_file->get_value( 'media_size' );
					}
					if ( $av_file->get_value( 'media_duration' ) )
					{
						$xtra_info[] = $av_file->get_value( 'media_duration' );
					}
					if ( $av_file->get_value( 'media_quality' ) )
					{
						$xtra_info[] = $av_file->get_value( 'media_quality' );
					}
					if( $av_file->get_value('default_media_delivery_method') )
					{
						$xtra_info[] = str_replace('_',' ',($av_file->get_value('default_media_delivery_method')));
					}
					$markup .= implode(', ',$xtra_info);
					$markup .= ')</span>'."\n";
				}
				if($is_current && $av_file->get_value('url'))
				{
					$this->displayer->set_current_media_file($av_file);
					$embed_markup = $this->displayer->get_display_markup();
					if(!empty($embed_markup))
					{
						$markup .= '<div class="player">'."\n".$embed_markup."\n".'</div>'."\n";

						$tech_note = $this->displayer->get_latest_tech_note();
						if(!empty($tech_note))
						{
							$markup .= '<div class="techNote">'.$tech_note.'</div>'."\n";
						}
					}
					$other_links = array();
					if($av_file->get_value('media_is_progressively_downloadable') == 'true')
					{
						$other_links[] = '<a href="'.alter_protocol($av_file->get_value('url'),'rtsp','http').'" title="Direct link to download &quot;'.htmlspecialchars($this->media_work->get_value('name').': '.$file_desc).'&quot;" class="download">Download file</a>';
					}
					if($av_file->get_value('media_is_streamed') == 'true')
					{
						$other_links[] = '<a href="'.alter_protocol($av_file->get_value('url'),'http','rtsp').'" title="Direct link to stream &quot;'.htmlspecialchars($this->media_work->get_value('name').': '.$file_desc).'&quot;" class="stream">Direct link to stream</a>';
					}
					if(empty($other_links))
					{
						$other_links[] = '<a href="'.$av_file->get_value('url').'" title="Direct link to &quot;'.htmlspecialchars($this->media_work->get_value('name').': '.$file_desc).'&quot;">Direct link to file</a>';
					}
					$markup .= '<p class="direct">'.implode(' ',$other_links).'</p>'."\n";
					
				}
				if(!$av_file->get_value( 'url' ))
				{
					$owner = $av_file->get_owner();
					if(!empty($owner) && $owner->get_value('name_cache') )
					{
						$phrase = 'File not available online. Please contact site maintainer ('.$owner->get_value('name_cache').') for this file.';
					}
					else
					{
						$phrase = 'File not available online. Please contact site maintainer for this file.';
					}
					$markup .= ' <em>'.$phrase.'</em>';
				}
				$markup .= "</li>\n";
				$prev_format = $av_file->get_value( 'media_format' );
			}
			$markup .= '</ul>'."\n";
			$markup .= '</div>'."\n";
		}
	
		return $markup;
	}
	
	public function set_media_width($width)
	{
		$this->displayer->set_width($width);
	}
	
	public function set_media_height($height)
	{
		$this->displayer->set_height($height);
	}
	
	public function set_current_media_file($media_file)
	{
		$this->displayer->set_current_media_file($media_file);
	}
	
	public function set_google_analytics($on)
	{}
}
?>