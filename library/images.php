<?php
/**
 * @package	Magic image resize
 * @subpackage  Content.Miresize
 * @copyright	Copyright 2021 (C) computer.daten.netze::feenders. All rights reserved.
 * @license		GNU/GPL, see LICENSE.txt
 * @author		Dirk Hoeschen (hoeschen@feenders.de)
 * @version    1.1
 */

// No direct access
defined('_JEXEC') or die;

/**
 * Images helper functions
 */
class MiresizeImages  extends JHelper
{

	// Jpeg Image Quality between 0 and 100 (no compression)
	public $quality= 85;

	// Overlay Watermark
	public $watermark = 0;

	// Watermak Image - Optional with complete path - must be a semitransparent png
	public $watermark_img = "media/plg_content_miresize/images/watermark.png";

	// Transparency for watermark between 0 an 100;
	public $watermark_alpha = 50;

	// Background color in rgb values for the passepartout 40 = dark gray
	public $bgcolor = '#666666';

	/**
	 * Create a scaled or cropped version of a image inside a .thumbs folder
	 *
	 * @param   string  $image
	 * @param   int     $size_w
	 * @param   int     $size_h
	 * @param   string  $mode (scale,crop,fit)
	 * @param   string  $format (jpg,webp)
	 *
	 * @return false|string
	 *
	 * @since 1.0
	 */
	public function getThumb(string $image,int $size_w=480, int $size_h=480, $mode="scale", string $format="jpg" ) {
		try
		{
			$pp = pathinfo($image);
			$pp['dirname'] = str_replace(JUri::root(false),"",$pp['dirname']);
			$path = "/".trim($pp['dirname'],"/");
			$image = $path."/".$pp['basename'];
			if (!file_exists(JPATH_ROOT.$image) || !is_file(JPATH_ROOT.$image)) {
				throw new Exception("Image ".$image." not found.");
			}
			$tfolder = "/.thumbs/".$size_w."-".$size_h."-".$mode[0]."/";
			if (strpos($pp['dirname'],$tfolder)===false) $path .= $tfolder;
			$thumb = $path.JFilterOutput::stringURLSafe($pp['filename']).".jpg";
			if (!file_exists(JPATH_ROOT.$path) || !is_dir(JPATH_ROOT.$path)) {
				if (!mkdir(JPATH_ROOT.$path,0775,true)) {
					throw new Exception("Can not create ".$path.". Make sure the image folders are writable.");
				}
			}

			// create hash for update check
			$hash = md5_file(JPATH_ROOT.$image);
			$hf =  JPATH_ROOT.$path.$hash.".md5";

			if (!file_exists(JPATH_ROOT.$thumb ) || !file_exists($hf)) {
				$this->resizeImage(JPATH_ROOT . $image, JPATH_ROOT . $thumb, $size_w, $size_h, $mode,$format);
				touch($hf);
			}
		} catch (Exception $e) {
			error_log("ProcessImg: ".$e->getMessage(),0);
			return false;
		}

		return $thumb;
	}

	/**
	 * Create - Create a new JPG-image from PNG oder JPG at destination
	 * Optionally grayscale or add watermark
	 *
	 * Mode options:
	 * scale = resize and keep ascpect
	 * crop = crop image to the exact with and height
	 * fit = fit image into a passepartout
	 *
	 * @param string $source - Name with path of the source file
	 * @param string $destination  - Name with path of the destination file
	 * @param int $size_w - Destination width
	 * @param int $size_h - Destination height
	 * @param string $mode (scale, crop, fit)
	 * @param boolean $grayscale
	 * @return boolean
	 */
	public function resizeImage($source,$destination, $size_w=200, $size_h=200, $mode="scale",$grayscale=false) {
		try {
			if (file_exists($source)) {
				if ($imageinfo = getimagesize($source)) {
					switch($imageinfo[2]) {
						case IMAGETYPE_JPEG:
							$src_img = imagecreatefromjpeg($source);
							break;
						case IMAGETYPE_PNG:
							$src_img = imagecreatefrompng($source);
							break;
						case IMAGETYPE_GIF:
							$src_img = imagecreatefromgif($source);
							break;
						default:
							throw new Exception("Image must be of type JPG, PNG or GIF.");
					}
				} else {
					throw new Exception("Can not get imageinfo from (".$source."). Is it a image?");
				}
			} else {
				throw new Exception("Can not open (".$source."). File does not exist or is not readable.");
			}
		} catch (Exception $e) {
			error_log("ProcessImg: ".$e->getMessage(),0);
			return false;
		}

		if (isset($src_img)) {
			$src_width = $imageinfo[0];
			$src_height = $imageinfo[1];
			if($src_width>=$src_height) { // Aspect x>y
				$new_w = $size_w;
				$new_h = abs(($size_w/$src_width)*$src_height);
				if ($new_h>=$size_h) {
					$new_h = $size_h;
					$new_w = abs(($size_h/$src_height)*$src_width);
				}
			} else {
				$new_h = $size_h;
				$new_w = abs(($size_h/$src_height)*$src_width);
				if ($new_w>=$size_w) {
					$new_w = $size_w;
					$new_h = abs(($size_w/$src_width)*$src_height);
				}
			}
			switch ($mode) {
				case "crop" : // Crop to target size
					$dst_img = imagecreatetruecolor($size_w,$size_h);
					$a_new = ($size_w/$size_h);
					$a_old = ($src_width/$src_height);
					if($a_new>=$a_old) { // crop height
						$sx=0;
						$src_height = (int)($src_width/$a_new);
						$sy = (int)(($imageinfo[1]-$src_height)/2);
					} else { // crop width
						$sy=0;
						$src_width = (int)($src_height/($size_h/$size_w));
						$sx = (int)(($imageinfo[0]-$src_width)/2);
					}
					imagecopyresampled($dst_img,$src_img,0,0,$sx,$sy,$size_w,$size_h,$src_width,$src_height);
					break;
				case "fit" : // Fit into target size
					$dst_img = imagecreatetruecolor($size_w,$size_h);
					$bgcolor = array(hexdec(substr($this->bgcolor,1,2)),hexdec(substr($this->bgcolor,3,2)),hexdec(substr($this->bgcolor,5,2)));
					$color = imagecolorallocate($dst_img, $bgcolor[0], $bgcolor[1], $bgcolor[2]);
					$a_new = ($size_w/$size_h);
					$a_old = ($src_width/$src_height);
					if($a_old>=$a_new) { // fit width
						$dx = 0;
						$new_w = (int) $size_w;
						$new_h = (int)($imageinfo[1]*($new_w/$imageinfo[0]));
						$dy = (int)(($size_h-$new_h)/2);
					} else { // fit height
						$dy = 0;
						$new_h = (int) $size_h;
						$new_w = (int)($imageinfo[0]*($new_h/$imageinfo[1]));
						$dx = (int)(($size_w-$new_w)/2);
					}
					imagefill($dst_img, 0, 0, $color);
					imagecopyresampled($dst_img,$src_img,$dx,$dy,0,0,$new_w,$new_h,$imageinfo[0],$imageinfo[1]);
					break;
				case "scale" : // Scale (keep aspect)
				default:
					if ($src_width<$new_w) {
						$new_h = $src_height;
						$new_w = $src_width;
					}
					$dst_img = imagecreatetruecolor($new_w,$new_h);
					imagecopyresampled($dst_img,$src_img,0,0,0,0,$new_w,$new_h,$imageinfo[0],$imageinfo[1]);
					break;
			}

			// get real dimensions
			$new_w = imagesx( $dst_img );
			$new_h = imagesy( $dst_img );

			// grayscale image
			if ($grayscale===true) {
				$bwimage= imagecreate($new_w,$new_h);
				$palette = array();
				//Creates the 256 color palette
				for ($c=0;$c<256;$c++){ $palette[$c] = imagecolorallocate($bwimage,$c,$c,$c);}
				//Reads the origonal colors pixel by pixel
				for ($y=0;$y<$new_h;$y++) {
					for ($x=0;$x<$new_w;$x++) {
						$rgb = imagecolorat($dst_img,$x,$y);
						$r = ($rgb >> 16) & 0xFF; $g = ($rgb >> 8) & 0xFF; $b = $rgb & 0xFF;
						//This is where we actually use yiq to modify our rbg values, and then convert them to our grayscale palette
						$gs = (($r*0.299)+($g*0.587)+($b*0.114));
						imagesetpixel($bwimage,$x,$y,$palette[$gs]);
					}
				}
				$dst_img = $bwimage;
			}

			// watermark image
			if ($this->watermark==1) {
				// test watermark image dimensions and type
				$imageinfo = getimagesize(JPATH_ROOT."/".$this->watermark_img);
				if (!$imageinfo || $imageinfo[2]!=IMAGETYPE_PNG) {
					error_log("ProcessImg: Watermark image must be PNG with alpha channel",0);
				} else {
					if ($rWatermark = imagecreatefrompng(JPATH_ROOT."/".$this->watermark_img)) {
						$w_w =  ($imageinfo[0]<$new_w) ? $imageinfo[0] : $new_w;
						$w_h =  ($imageinfo[1]<$new_h) ? $imageinfo[1] : $new_h;
						$this->imagecopymergeAlpha($dst_img, $rWatermark, 0,0,0,0, $w_w,$w_h,((int)$this->watermark_alpha%100));
						imagedestroy($rWatermark);
					}
				}
			}

			try {
				if (!imagejpeg($dst_img, $destination, $this->quality)) {
					throw new Exception("Can not create destination (".$dst_img.") Check path and acces rights");
				}
			} catch (Exception $e) {
				error_log("ProcessImg: ".$e->getMessage(),0);
				return false;
			}

			imagedestroy($dst_img);
			return true;
		}
		error_log("ProcessImg: Something went wrong",0);
		return false;
	}

	/**
	 * A fix to get a function like imagecopymerge WITH ALPHA SUPPORT
	 * Nessecary to attach watermark
	 *
	 * @param $dst_img
	 * @param $src_img
	 * @param $dst_x
	 * @param $dst_y
	 * @param $src_x
	 * @param $src_y
	 * @param $w
	 * @param $h
	 * @param $pct
	 * @return bool
	 */
	protected function imagecopymergeAlpha(&$dst_img, $src_img, $dst_x, $dst_y, $src_x, $src_y, $w, $h,$pct=20) {
		$pct /= 100;
		// Get image width and height
		// Turn alpha blending off
		imagealphablending( $src_img, false );
		// Find the most opaque pixel in the image (the one with the smallest alpha value)
		$minalpha = 127;
		for( $y = 0; $y < $h; $y++ ){
			for( $x = 0; $x < $w; $x++ ) {
				$alpha = ( imagecolorat( $src_img, $x, $y ) >> 24 ) & 0xFF;
				if( $alpha < $minalpha ){
					$minalpha = $alpha;
				}
			}
		}
		//loop through image pixels and modify alpha for each
		for( $y = 0; $y < $h; $y++ ){
			for( $x = 0; $x < $w; $x++ ){
				//get current alpha value (represents the TANSPARENCY!)
				$colorxy = imagecolorat( $src_img, $x, $y );
				$alpha = ( $colorxy >> 24 ) & 0xFF;
				//calculate new alpha
				if( $minalpha !== 127 ){
					$alpha = 127 + 127 * $pct * ( $alpha - 127 ) / ( 127 - $minalpha );
				} else {
					$alpha += 127 * $pct;
				}
				//get the color index with new alpha
				$alphacolorxy = imagecolorallocatealpha( $src_img, ( $colorxy >> 16 ) & 0xFF, ( $colorxy >> 8 ) & 0xFF, $colorxy & 0xFF, $alpha );
				//set pixel with the new color + opacity
				if( !imagesetpixel( $src_img, $x, $y, $alphacolorxy ) ){
					return false;
				}
			}
		}
		// The image copy
		imagecopy($dst_img, $src_img, $dst_x, $dst_y, $src_x, $src_y, $w, $h);
	}

}
