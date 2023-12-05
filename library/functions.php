<?php
/**
 *
 * Plugin to resize content images automatically if tagged with a data-resize attribute
 *
 * @package	Magic image resize
 * @subpackage  Content.Miresize
 * @copyright	Copyright 2023 (C) computer.daten.netze::feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.txt
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 * @version    1.5
 *
 **/

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

// No direct access
defined('_JEXEC') or die;

/**
 * Magic Image Resize helper functions
 */
class MiresizeFunctions
{

	/**
	 * Clean filename
	 *
	 *
	 * @param      $filename
	 * @param bool $beautify
	 *
	 * @return string|string[]|null
	 *
	 * @since 0.9
	 */
	public function filterFilename($filename) {
		$filename = trim($filename);
		$filename = str_replace('-', ' ', $filename);

		$lang = Factory::getApplication()->getLanguage();
		$filename = $lang->transliterate($filename);
		$filename = preg_replace('/(\s|[^A-Za-z0-9\-\.])+/', '-', $filename);
		$filename = trim($filename, '-');

		// sanitize filename
		$filename = preg_replace(
			'~
        [<>:"/\\|?*]|            # file system reserved https://en.wikipedia.org/wiki/Filename#Reserved_characters_and_words
        [\x00-\x1F]|             # control characters http://msdn.microsoft.com/en-us/library/windows/desktop/aa365247%28v=vs.85%29.aspx
        [\x7F\xA0\xAD]|          # non-printing characters DEL, NO-BREAK SPACE, SOFT HYPHEN
        [#\[\]@!$&\'()+,;=]|     # URI reserved https://tools.ietf.org/html/rfc3986#section-2.2
        [{}^\~`]                 # URL unsafe characters https://www.ietf.org/rfc/rfc1738.txt
        ~x',
			'-', $filename);
		// avoids ".", ".." or ".hiddenFiles"
		$filename = ltrim($filename, '.-');
		// maximize filename length to 255 bytes http://serverfault.com/a/9548/44086
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		$filename = mb_strcut(pathinfo($filename, PATHINFO_FILENAME), 0, 255 - ($ext ? strlen($ext) + 1 : 0), mb_detect_encoding($filename)) . ($ext ? '.' . $ext : '');
		// reduce consecutive characters
		$filename = preg_replace(array( '/ +/', '/_+/','/-+/' ), '-', $filename);
		$filename = preg_replace(array('/-*\.-*/','/\.{2,}/'), '.', $filename);
		return $filename;
	}
	
	
	
	/**
	 * Remove all image folders recursively
	 *
	 * @since 0.9
	 */
	public function clearThumbCache() {
		$found = 0;
		$cparams  = ComponentHelper::getParams('com_media');
		$it = new RecursiveDirectoryIterator(JPATH_SITE . '/' . $cparams->get('image_path'));
		foreach(new RecursiveIteratorIterator($it) as $file)
		{
			if (substr($file,-9) === '.thumbs/.') {
				$found++;
				$this->rrmdir(substr($file,0,-2));
			}
		}
	}
	
	/**
	 * @param $dir
	 *
	 * @since  0.9
	 */
	protected function rrmdir($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (is_dir($dir."/".$object) && !is_link($dir."/".$object))
						$this->rrmdir($dir."/".$object);
					else
						unlink($dir."/".$object);
				}
			}
			rmdir($dir);
		}
	}

}
