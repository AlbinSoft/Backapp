<?

	namespace EngineFwk;

	define('HTTP_LOG',       PATH_ROOT.'http.log');
	define('HTTP_COOKIES',   PATH_ROOT.'http-cookies.txt');
	define('HTTP_USERAGENT', 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0');

	class HTTP extends \stdClass {

		static public function fetch_file($url, $path = null) {
			if(!empty($path) && is_dir($path)) {
				$path .= '/'.basename($url);
				$exists = file_exists($path);
			} else {
				$exists = false;
			}
			if(empty($path) || !$exists) {
				if($ch = curl_init($url)) {
					curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					$rs = curl_exec($ch);
					$in = curl_getinfo($ch);
					curl_close($ch);
					file_put_contents(HTTP_LOG, print_r($in, true), FILE_APPEND);
					file_put_contents(HTTP_LOG, print_r($rs, true).PHP_EOL, FILE_APPEND);
					$ok = ($in['http_code']==200 || $in['http_code']==400);
					if($ok) {
						if(!empty($path)) {
							file_put_contents($path, $rs);
							return $path;
						}
						return $rs;
					}
				}
			} elseif($exists) {
				return $path;
			}
			return empty($path) ? false : null;
		}

		static public function get($url) {
			if($ch = curl_init($url)) {
				$headers = [];
				if(defined('API_TOKEN')) {
					$headers[] = 'Authorization: Bearer '.API_TOKEN;
				}
				//	'Accept: application/json'
				if(defined('HTTP_COOKIES')) {
					curl_setopt($ch, CURLOPT_COOKIEFILE, HTTP_COOKIES);
					curl_setopt($ch, CURLOPT_COOKIEJAR,  HTTP_COOKIES);
				}
				if(defined('HTTP_USERAGENT')) {
					curl_setopt($curl, CURLOPT_USERAGENT, HTTP_USERAGENT);
				}
				curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$rs = curl_exec($ch);
				$in = curl_getinfo($ch);
				curl_close($ch);
				file_put_contents(HTTP_LOG, print_r($in, true), FILE_APPEND);
				file_put_contents(HTTP_LOG, print_r($rs, true).PHP_EOL, FILE_APPEND);
				$ok = ($in['http_code']==200 || $in['http_code']==400);
				if($ok) {
					return $rs;
				}
			}
			return false;
		}

		static public function post($url, $data) {
			if($ch = curl_init($url)) {
				$data = json_encode($data);
				curl_setopt($ch, CURLOPT_VERBOSE, 1);
				curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				curl_setopt($ch, CURLOPT_HTTPHEADER, [
					 'Authorization: Bearer '.API_TOKEN,
					 'Accept: application/json',
				//	 'Content-Length: '.strlen($data),
					 'Content-Type: application/json'
				]);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$rs = curl_exec($ch);
				$in = curl_getinfo($ch);
				curl_close($ch);
				file_put_contents(HTTP_LOG, print_r($in, true), FILE_APPEND);
				file_put_contents(HTTP_LOG, print_r($rs, true).PHP_EOL, FILE_APPEND);
				$ok = ($in['http_code']==200 || $in['http_code']==400);
				if($ok) {
					return $rs;
				}
			}
			return false;
		}

		static public function get_json($url) {
			$data = self::get($url);
			if(FALSE!==$data) {
				return json_decode($data);
			}
			return null;
		}

		static public function post_json($uri, $data) {
			$data = self::post($uri, $data);
			if(FALSE!==$data) {
				return json_decode($data);
			}
			return null;
		}

	}
