<?php
/**
 *
 * Plugin to resize content images automatically if tagged with a data-resize attribute
 *
 * @package	Magic image resize
 * @subpackage  Content.Miresize
 * @copyright	Copyright 2021 (C) computer.daten.netze::feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.txt
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 * @version    1.1
 *
 **/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\Registry\Registry;
JLoader::register('MiresizeFunctions', JPATH_PLUGINS . '/content/miresize/library/functions.php');
JLoader::register('MiresizeImages', JPATH_PLUGINS . '/content/miresize/library/images.php');

class plgContentMiResize extends JPlugin {

	public function __construct( &$subject, $config = array() ) {
		parent::__construct( $subject, $config );
		// Clear all thumbs and reset config
		if ($this->params->get('reset_thumbs','0')=='1') {
			$rsh = new MiresizeFunctions();
			$rsh->clearThumbCache();
			$this->params->set('reset_thumbs','0');
			$pstring = json_encode($this->params);
			$db = \Joomla\CMS\Factory::getDbo();
			$db->setQuery("UPDATE `#__extensions` SET `params`=".$db->quote($pstring)." WHERE `type` LIKE 'plugin' AND `element` = 'miresize' AND `folder` = 'content'");
			$db->execute();
		}
	}

	/**
	 * Plugin that replaces image src with a reduced copy
	 *
	 * @param	string	The context of the content being passed to the plugin.
	 * @param	mixed	An object with a "text" property or the string to be cloaked.
	 * @param	array	Additional parameters.
	 * @param	int		Optional page number. Unused. Defaults to zero.
	 * @return	boolean	True on success.
	 */
	public function onContentPrepare($context, &$row, &$params, $limitstart=0 ) {
		// simple check for existance
		if (strpos($row->text,"data-resize=")!== false) {
			return $this->_getNewContent($row, $params);
		}
		return true;
	}

	/**
	 * Parse article content and replace finds ...
	 *
	 * @param object $row
	 * @param object $params
	 */
	protected function _getNewContent(&$row, &$params ) {
		// find tags in content-text
		$mir = new MiresizeImages();
		$mir->bgcolor = $this->params->get('fit_bg','#666666');
		$mir->quality = (int) $this->params->get('img_quality',85);
		$mir->watermark = (int) $this->params->get('watermark',0);
		if ($mir->watermark==1) {
			$mir->watermark_alpha = (int) $this->params->get('watermark_alpha',50);
			$mir->watermark_img = $this->params->get('watermark_img','media/plg_content_miresize/images/watermark.png');
		}

		preg_match_all('/(<img[^>]+>)/i',$row->text, $matches);
		if (!empty($matches)) {
			foreach($matches[0] as $n => $img) {
				if (strpos($img,"data-resize=")!== false) {
					preg_match( '@src="([^"]+)"@' , $img, $match );
					$src = array_pop($match);
					preg_match( '@data-resize="([^"]+)"@' , $img, $match );
					$mode = strtolower(array_pop($match));
					preg_match( '@width="([^"]+)"@' , $img, $match );
					$width = (int)(array_pop($match));
					preg_match( '@height="([^"]+)"@' , $img, $match );
					$height = (int)(array_pop($match));
					if (empty($width)) {
						$width = $this->params->get('width', '800');
					}
					if (empty($height)) {
						$height = $this->params->get('height', '800');
					}
					if ($mode!="scale" && $mode!="crop" && $mode!="fit") {
						$mode = $this->params->get('mode', 'scale');
					}
					// get thumbnail image
					$image =  $mir->getThumb($src,$width,$height,$mode);
					if (!empty($image)) {
						$new_tag = str_replace($src, JUri::root(true).$image, $img);
						// Ad lazyload attribute
						if ($this->params->get('img_lazyload',0)==1) {
							$new_tag = preg_replace('/<img /i','<img loading="lazy" ',$new_tag);
						}
						$row->text = str_replace( $img,$new_tag,$row->text);
					}
				}
			}
		}
		return true;
	}

}
