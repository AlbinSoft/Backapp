<?

	class URL {

		static public function myself() {
			return new URL(url($_SERVER['REQUEST_URI']));
		}

		private $original = null;

		public function __construct($url = null, $suffix = null) {
		//	$url = parse_url($url);
		//	$base = "{$url['scheme']}://{$url['host']}{$url['path']}";
			$this->original = !empty($url) ? $url : URI_QS;
			$this->base   = URL_ROOT;
			$this->path   = substr(URI, 1);
			$this->suffix = '';
			$this->qs     = parse_url($url, PHP_URL_QUERY);
			$this->qsa    = [];

			parse_str($this->qs, $this->qsa);
			if(is_string($suffix)) {
				$this->suffix = re_substr($suffix, $this->path);
				$this->path  = str_replace($this->suffix, '', $this->path);
			}
		}

		public function set_path($path) {
			if($path[0]=='/') {
				$this->path = substr($path, 1);
			} else {
				$this->path = $path;
			}
			return $this;
		}

		public function set_suffix($suffix) {
			if($suffix[0]=='/') {
				$this->suffix = substr($suffix, 1);
			} else {
				$this->suffix = $suffix;
			}
			return $this;
		}

		public function get_param($key) {
			return isset($this->qsa[$key]) ? $this->qsa[$key] : null;
		}

		public function add_param($key, $val) {
			$akey = $key.'[]';
			$this->qsa[$akey] = $val;
			return $this;
		}

		public function set_param($key, $val) {
			$this->qsa[$key] = $val;
			return $this;
		}

		public function set_params($params) {
			$this->qsa = array_merge($this->qsa, $params);
			return $this;
		}

		public function rem_param($key) {
			$akey = $key.'[]';
			$this->qsa = array_filter($this->qsa, function ($k) use ($key, $akey) {
				return $k!==$key && $k!==$akey;
			}, ARRAY_FILTER_USE_KEY);
		//	unset($this->qsa[$key]);
			return this;
		}

		public function get_url_base() {
			return $this->base.$this->path;
		}

		public function string() {
			$qs = http_build_query($this->qsa);
			if(!empty($qs)) $qs = '?'.$qs;
			return $this->base.$this->path.$this->suffix.$qs;
		}

	}