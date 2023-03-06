<?php

	namespace EngineFwk;

	class Images extends \stdClass {

		private static $instance = null;

		public static function getInstance($conf_name = false) {
			if(self::$instance!==null)
				return self::$instance;
			self::$instance = new Images();
			return self::$instance;
		}

		function info($path) {
			$retval = array();
			if(file_exists($path)) {
				$img_src = self::openimage($path);
				$retval["width"]  = imagesx($img_src);
				$retval["height"] = imagesy($img_src);
				imagedestroy($im);
			} else {
				$retval["width"]  = 0;
				$retval["height"] = 0;
			}
			return (object) $retval;
		}

		function rotateImage($path_src, $degrees, $path_trg = null, $q = null) {
			if(!file_exists($path_src)) throw new Exception('File not found.');
			$path_trg = $path_trg ?? $path_src;
			$degrees  = (int) $degrees;
			if(($degrees %90)!==0) throw new Exception('Degrees availables: 90, 180 or 270.');
			list($w, $h) = getimagesize($path_src);
			$src = self::openimage($path_src);
			$bg  = imagecolorallocatealpha($src, 0, 0, 0, 0);
			$trg = imagerotate($src, $degrees, $bg);
			if($degrees===180) {
				imageflip($trg, IMG_FLIP_VERTICAL);
			}
			imagedestroy($src);
			self::saveimage($path_trg, $trg, $q);
		}

		function convert($path_src, $path_trg, $q = null) {
			$img = self::openimage($path_src);
			self::saveimage($path_trg, $img, $q);
		}

		function crop($path_src, $path_trg, $width, $height, $q = null) {
			$src = self::openimage($path_src);
			$w = imagesx($src);
			$h = imagesy($src);
			if($width/$height>$w/$h) {
				$nh = ($h/$w)*$width;
				$nw = $width;
			} else {
				$nw = ($w/$h)*$height;
				$nh = $height;
			}
			$dx = ($width/2)-($nw/2);
			$dy = ($height/2)-($nh/2);
			$trg = imageCreateTrueColor($width, $height);
			imagecopyresampled($trg, $src, $dx, $dy, 0, 0, $nw, $nh, $w, $h);
			imagedestroy($src);
			self::saveimage($path_trg, $trg, $q);
		}

		function fit($path_src, $path_trg, $maxw, $maxh, $q = null) {
			$src = self::openimage($path_src);
			$w = imagesx($src);
			$h = imagesy($src);
			if($w<=$maxw && $h<=$maxh) {
				imagedestroy($src);
				copy($path_src, $path_trg);
			} else {
				$co = $w    / $h;
				$cd = $maxw / $maxh;
				if($cd<$co) {
					$calw = $maxw;
					$calh = ($h/$w)*$maxw;
				} else {
					$calw = ($w/$h)*$maxh;
					$calh = $maxh;
				}
				$trg = imagecreatetruecolor($calw, $calh);
				$bg  = imagecolorallocatealpha($trg, 255, 255, 255, 0);
				imagefill($trg, 0, 0 , $bg);
				imagecolortransparent($trg, $bg);
			//	imagecopymerge($trg, $src, 0, 0, 0, 0, $calw, $calh, 100);
				imagecopyresampled($trg, $src, 0, 0, 0, 0, $calw, $calh, $w, $h);
				imagedestroy($src);
				self::saveimage($path_trg, $trg, $q);
			}
		}

		function openimage($path) {
			if(!file_exists($path)) throw new Exception('File not found.');
			switch(substr($path, -3)) {
				case "gif":
					$img = imagecreatefromgif ($path); break;
				case "jpg":
					$img = imagecreatefromjpeg($path); break;
				case "png":
					$img = imagecreatefrompng ($path); break;
			}
			if(!is_resource($img)) throw new Exception('Invalid image format.');
			return $img;
		}

		function saveimage($path, $img, $q) {
			if(!is_resource($img)) throw new Exception('Invalid image resource.');
			switch(substr($path, -3)) {
				case "gif":
					imagegif ($img, $path); break;
				case "jpg":
					imagejpeg($img, $path); break;
				case "png":
					imagepng ($img, $path); break;
			}
			imagedestroy($img);
		}

	}
