<?php
/**
 * A factory that grabs the appropriate media work handler classes.
 *
 * @author Marcus Huderle
 */
class MediaWorkFactory
{	
	/**
	 * Grabs an appropriate displayer instance for the given entity of library.
	 * Return false if something went wrong.
	 */
	public static function media_work_displayer($entity_or_library)
	{
		return self::_construct($entity_or_library, 'media_work_displayer');
	}
	
	/**
	 * Grabs an appropriate shim instance for the given entity or library.
	 * Returns false if something went wrong.
	 */
	public static function shim($entity_or_library)
	{
		return self::_construct($entity_or_library, 'shim');
	}
	
	/**
	 * Grabs an appropriate content manager modifier instance for the given entity or library.
	 * Return false if something went wrong.
	 */
	public static function media_work_content_manager_modifier($entity_or_library)
	{
		return self::_construct($entity_or_library, 'media_work_content_manager_modifier');
	}
	
	/**
	 * Grabs an appropriate previewer modifier instance for the given entity or library.
	 * Return false if something went wrong.
	 */
	public static function media_work_previewer_modifier($entity_or_library)
	{
		return self::_construct($entity_or_library, 'media_work_previewer_modifier');	
	}
	
	/**
	 * Grabs an appropriate media file content manager modifier instance for the given entity or 
	 * library. Returns false if something went wrong.
	*/
	public static function media_file_content_manager_modifier($entity_or_library)
	{
		return self::_construct($entity_or_library, 'media_file_content_manager_modifier');
	}
	
	/**
	 * Grabs an appropriate media work size selector instance for the given entity or
	 * library. Returns false if something went wrong.
	 */
	public static function media_work_size_selector($entity_or_library)
	{
		return self::_construct($entity_or_library, 'media_work_size_selector');
	}
	
	public static function displayer_chrome($entity_or_library, $type)
	{
		return self::_construct($entity_or_library, 'displayer_chrome', 'classes/media/'.self::_get_library($entity_or_library).'/displayer_chrome/'.$type.'.php', $type);
	}
	
	/**
	 * Gets the correct class for the given library and module. More documentation to come...
	 */
	private static function _construct($entity_or_library, $module, $filename = false, $meta_info = false)
	{
		$library = self::_get_library($entity_or_library);
		if (!$filename)
		{
			$filename = 'classes/media/'.$library.'/'.$module.'.php';
		}
		if (reason_file_exists($filename))
		{
			reason_include_once($filename);
			$classname = self::_convert_to_camel_case($module);
			if ($meta_info)
			{
				$library_classname = ucfirst($library).$meta_info.$classname;
			}
			else
			{
				$library_classname = ucfirst($library).$classname;
			}
			if (class_exists($library_classname))
			{
				$interface_file = 'classes/media/interfaces/'.$module.'_interface.php';
				if (reason_file_exists($interface_file))
				{
					reason_include_once($interface_file);
					$class = new $library_classname();
					$implemented = class_implements($class);
					if (isset($implemented[$classname.'Interface'])) {
						return $class;
					} else {
						trigger_error($library_classname.' must implement '.$classname.'Interface.');
					}
				}
				else {
					trigger_error($module.' interface file does not exist: '.$interface_file);
				}
			} else {
				trigger_error($module.' class does not exist: '.$library_classname);
			}
		} else {
			trigger_error($module.' file does not exist: '.$filename);
		}
	}
	
	/**
	 * Returns the transcoding library for the given entity or library.
	 */ 
	private static function _get_library($entity_or_library)
	{
		if (is_object($entity_or_library)) {
			$lib = $entity_or_library->get_value('integration_library');
			if ($lib)
			{
				if(strpos('/',$lib))
				{
					trigger_error('Media integration libraries may only be placed directly in classes/media/. It appears that this library was placed in a subdirectory or elsewhere ('.$entity_or_library.')');
					return 'default';
				}
				return $lib;
			}
			else
				return 'default';
		} else if (is_string($entity_or_library)) {
			if ($entity_or_library)
			{
				if(strpos('/',$entity_or_library))
				{
					trigger_error('Media integration libraries may only be placed directly in classes/media/. It appears that this library was placed in a subdirectory or elsewhere ('.$entity_or_library.')');
					return 'default';
				}
				return $entity_or_library;
			}
			else return 'default';
		} else {
			return 'default';
		}
	}
	
	/**
	 * Generates the classname from the module name. 
	 * ex: media_work_previewer_modifier -> MediaWorkPreviewerModifier
	 */
	private static function _convert_to_camel_case($module) 
	{
		$parts = explode('_', $module);
		$classname = '';
		foreach ($parts as $word) {
			$classname .= ucfirst($word);
		}
		return $classname;
	}
	
}
?>