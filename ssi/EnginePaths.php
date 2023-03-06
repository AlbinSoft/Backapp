<?

	namespace EngineFwk;

	!defined('PATH_ROOT') && define('PATH_ROOT', $_SERVER['DOCUMENT_ROOT']);

	trait EnginePaths {

		static public function getFilePath($filename, $path = null) {
			$default   = self::trailingslash(dirname(__FILE__));
			$filenames = !is_array($filename) ? [$filename] : $filename;
			foreach($filenames as $filename) switch(true) {
				case !empty($path)        && is_file($path.$filename):     return $path.$filename;
				case defined('PATH_LOGS') && is_file(PATH_LOGS.$filename): return PATH_LOGS.$filename;
				case defined('LOGS_PATH') && is_file(LOGS_PATH.$filename): return LOGS_PATH.$filename;
				case defined('PATH_ROOT') && is_file(PATH_ROOT.$filename): return PATH_ROOT.$filename;
				default:                  if(is_file($default.$filename))  return $default.$filename;
			}
			return null;
		}

		static public function getLogPath($filename) {
			switch(true) {
				case defined('PATH_LOGS'): return PATH_LOGS.$filename;
				case defined('LOGS_PATH'): return LOGS_PATH.$filename;
				case defined('PATH_ROOT'): return PATH_ROOT.$filename;
			}
			return null;
		}

		static public function trailingslash($path) {
			$path  = rtrim($path, '/');
			$path .= '/';
			return $path;
		}

	}
