<?

	namespace Arrakis;

	/*
	 * wp_upload_dir( $time, $mkdir, $refresh_cache )
	 *
	 * path, subdir, basedir
	 * url,  baseurl
	 * error
	 */

	class FileUpload {

		function __construct($filefield) {
			$this->udir     = wp_upload_dir();
			if(is_string($filefield)) {
				$filefield = $_FILES[$filefield];
			}
			if(is_array($filefield)) {
				$this->filefield = (object) $filefield;
			} else {
				throw new Exception('Campo de fichero no encontrado');
			}
		}

		function save($filename, $basepath = null, $cb = null) {
			$basepath = $basepath ?? $this->udir['path'];

			if($this->filefield->error==UPLOAD_ERR_OK) {
				$filename = empty($filename) ? basename($this->filefield->name) : $filename;
				if(!file_exists($basepath)) mkdir($basepath, 0775, true);
				if(file_exists($basepath)) {
					$filename = str_replace(' ', '-', $filename);
					$filepath = $basepath.$filename;
					if(move_uploaded_file ($this->filefield->tmp_name, $filepath)) {
						$cb($filepath);
					} else {
						throw new Exception('No pudo moverse el fichero a la ruta '.$filepath);
					}
				} else {
					throw new Exception('No pudo crearse la carpeta '.$basepath);
				}
			} else {
				switch($this->filefield->error) {
					case UPLOAD_ERR_INI_SIZE:   throw new Exception('Demasiado grande #1');        break;
					case UPLOAD_ERR_FORM_SIZE:  throw new Exception('Demasiado grande #2');        break;
					case UPLOAD_ERR_PARTIAL:    throw new Exception('Carga interrumpida');         break;
					case UPLOAD_ERR_NO_FILE:    throw new Exception('No se envió ningún fichero'); break;
					case UPLOAD_ERR_NO_TMP_DIR: throw new Exception('Fallo de configuración #1');  break;
					case UPLOAD_ERR_CANT_WRITE: throw new Exception('Fallo de configuración #2');  break;
					case UPLOAD_ERR_EXTENSION:  throw new Exception('Fallo de configuración #3');  break;
				}
			}
			return $filepath;
		}

		function prefix($prefix) {
			return sprintf('%s-%s.%s', $prefix, pathinfo($this->filefield->name, PATHINFO_FILENAME), pathinfo($this->filefield->name, PATHINFO_EXTENSION));
		}

		function suffix($suffix) {
			return sprintf('%s-%s.%s', pathinfo($this->filefield->name, PATHINFO_FILENAME), $suffix, pathinfo($this->filefield->name, PATHINFO_EXTENSION));
		}

		function ext($filename) {
			return sprintf('%s.%s', $filename, pathinfo($this->filefield->name, PATHINFO_EXTENSION));
		}

		function dir($name) {
			return $this->udir['basedir'].'/'.$name.'/';
		}

		static function sanitize($filename) {
			$filename = basename($filename);
			// https://stackoverflow.com/questions/2021624/string-sanitizer-for-filename
		//	$toremove = implode('', array('á','à','â','ã','ª','ä','å','Á','À','Â','Ã','Ä','é','è','ê','ë','É','È','Ê','Ë','í','ì','î','ï','Í','Ì','Î','Ï','œ','ò','ó','ô','õ','º','ø','Ø','Ó','Ò','Ô','Õ','ú','ù','û','Ú','Ù','Û','ç','Ç','Ñ','ñ'));
			$filename = str_replace(' ', '-', $filename);
			$filename = mb_strtolower($filename);
			$filename = str_replace(array('á', 'é', 'í', 'ó', 'ú', 'ñ'), array('a', 'e', 'i', 'o', 'u', 'n'), $filename);
		//	$filename = mb_ereg_replace("([^\w\d\-_\[\]\(\)\.])", '',  $filename);
			$filename = mb_ereg_replace("([^\w\d\-_\.])",         '',  $filename);
			$filename = mb_ereg_replace("([\.]{2,})",             '.', $filename);
			$filename = mb_ereg_replace("([\-]{2,})",             '-', $filename);
			return $filename;
		}

		static function path2url($filepath) {
			$udir     = wp_upload_dir();
			$basepath = $udir['basedir'];
			$filepath = $udir['baseurl'].substr($filepath, strlen($basepath));
			return $filepath;
		}


	//	static function id2tree()


	// static function filenamesufix($filename, $sufix)
	// static function filenameprefix($filename, $prefix)
	// static function nocollition($filename, $path, $sufix='')

	}
