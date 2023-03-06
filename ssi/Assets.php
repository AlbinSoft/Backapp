<?

	namespace EngineFwk;

	class Assets {

		static $instance;

		static public function getInstance() {
			if(empty(self::$instance))
				self::$instance = new Assets();
			return self::$instance;
		}

		private $css_vars;
		private $css_code;
		private $css_files;
		private $css_urls;

		private $js_vars;
		private $js_code;
		private $js_files;
		private $js_urls;

		function __construct() {
			$this->log_on   = true;
			$this->path_log = PATH_LOGS.'assets.log';
			$this->log_on && file_exists($this->path_log) && unlink($this->path_log);
			$this->log('Assets construct for '.$_SERVER['REQUEST_URI']);

			$this->paths      = [];	// general

			$this->registered = []; // packs
			$this->enqueued   = []; // packs

			$this->css_vars  = [];
			$this->css_code  = [];
			$this->css_files = [];
			$this->css_urls  = []; // external / async
			$this->js_vars   = [];
			$this->js_code   = [];
			$this->js_files  = [];
			$this->js_urls   = []; // external / async
		}

		function log($line) {
			if($this->log_on) {
				if(substr($line, -strlen(PHP_EOL))!==PHP_EOL) $line .= PHP_EOL;
				file_put_contents($this->path_log, $line, FILE_APPEND);
			}
		}

		function init() {
			$this->log('init');
			ob_start([$this, 'replacements']);
		//	ob_start(function ($html, $phase) {
		//		$this->log('callback');
		//		return $this->replacements($html, $phase);
		//	});
			return $this;
		}

		function flush() {
			$this->log('flush');
			ob_end_flush();
		}

		function add_path($priority, $path) {
			if(!file_exists($path))
				error_log('Path not found «'.$path.'»'); // throw new Exception('Path not found «'.$path.'»');
			if(!isset($this->paths[$priority]))
				$this->paths[$priority] = [];
			$this->paths[$priority][] = $path;
			ksort($this->paths);
			$this->log('Add Path #'.$priority.' '.$path);
			return $this;
		}



		function add_css($file) {
			$this->add_css_file(10, $file);
		//	$this->inj_css[] = $file;
		//	$this->log('Add CSS:'.PHP_EOL.print_r($this->inj_css, true));
			return $this;
		}

		function add_css_var($name, $value) {
			$this->css_vars[$name] = $value;
			$this->log('Add CSS var: '.$name.':'.$value);
			return $this;
		}

		function add_css_code($priority, $code, $name='') {
			if(!empty($name))   $name = '/* '.$name.' */'.PHP_EOL;
			if(is_array($code)) $code = implode(PHP_EOL, $code);
			$this->css_code[$priority]   = $this->css_code[$priority] ?? [];
			$this->css_code[$priority][] = ($code = $name.trim($code));
			$this->log('Add CSS code: '.$priority.' '.substr($code, 0, strpos($code, PHP_EOL)));
			return $this;
		}

		function add_css_file($priority, $file) {
			$this->css_files[$priority]   = $this->css_files[$priority] ?? [];
			$this->css_files[$priority][] = $file;
			$this->log('Add CSS file: '.$priority.' '.$file);
			return $this;
		}

		function add_css_url($priority, $code) {
			$this->css_urls[$priority]   = $this->css_urls[$priority] ?? [];
			$this->css_urls[$priority][] = $url;
			$this->log('Add CSS url: '.$priority.' '.$url);
			return $this;
		}

		function has_css_file($file) {
			$has = false;
			foreach($this->css_files as $files) {
				$has = $has || (FALSE!==in_array($file, $files));
			}
			return $has;
		}



		function add_js($file) {
			$this->add_js_file(10, $file);
		//	$this->inj_js[] = $file;
		//	$this->log('Add JS:'.PHP_EOL.print_r($this->inj_js, true));
			return $this;
		}

		function add_js_var($name, $value) {
			$this->js_vars[$name] = $value;
		//	$this->log('Add JS var: '.$name); // .'='.$value);
			if(is_bool($value))    $this->log('Add JS var: '.$name.'='.($value ? 'true' : 'false'));
			if(is_numeric($value)) $this->log('Add JS var: '.$name.'='.$value.'');
			if(is_object($value))  $this->log('Add JS var: '.$name.'='.json_encode($value).'');
			if(is_array($value))   $this->log('Add JS var: '.$name.'='.json_encode($value).'');
			if(is_string($value))  $this->log('Add JS var: '.$name.'="'.$value.'"');
			return $this;
		}

		function add_js_code($priority, $code) {
			$this->js_code[$priority]   = $this->js_code[$priority] ?? [];
			$this->js_code[$priority][] = $code = trim($code);
			$this->log('Add JS code: '.$priority.' '.substr($code, 0, strpos($code, PHP_EOL)));
			return $this;
		}

		function add_js_file($priority, $file) {
			$this->js_files[$priority]   = $this->js_files[$priority] ?? [];
			$this->js_files[$priority][] = $file;
			$this->log('Add JS file: '.$priority.' '.$file);
			return $this;
		}

		function add_js_url($priority, $url) { // TODO , $attrs = 'defer'
			$this->js_urls[$priority]   = $this->js_urls[$priority] ?? [];
			$this->js_urls[$priority][] = $url;
			$this->log('Add JS url: '.$priority.' '.$url);
			return $this;
		}



		function define_pack($name, $css, $js) {
			$this->registered[$name] = $pack = [
				'css' => $css,
				'js'  => $js,
			];
			$this->log('Define Pack '.$name.':'.implode(' ', $pack));
			return $this;
		}

		function add_pack($name) {
			if(!isset($this->registered[$name]))
				error_log('Pack not found «'.$name.'»'); // throw new Exception('Pack not found «'.$name.'»');
			$this->enqueued[] = $name;
		//	$files = $this->registered[$name];
		//	if($files['css']) $this->add_css($files['css']);
		//	if($files['js'])  $this->add_js ($files['js']);
			$this->log('Add Pack: '.$name.PHP_EOL.print_r($this->enqueued, true));
			return $this;
		}

		function pack_exists($name) {
			$this->log('Pack Exists: '.$name.' '.(isset($this->registered[$name]) ? 'True' : 'False'), true);
			return isset($this->registered[$name]);
		}



		function replacements($html, $phase) {
		//	if() PHP_OUTPUT_HANDLER_CLEANABLE PHP_OUTPUT_HANDLER_FLUSHABLE PHP_OUTPUT_HANDLER_REMOVABLE
			/*
			$backtrace = debug_backtrace();
			foreach($backtrace as &$trace) {
				unset($trace['args']);
				unset($trace['object']);
				unset($trace);
			}
			error_log(print_r($backtrace, true));
			*/
			foreach($this->enqueued as $packname) {
				$files = $this->registered[$packname];
				if(!empty($files)) {
					if($files['css']) $this->add_css($files['css']);
					if($files['js'])  $this->add_js ($files['js']);
				}
			}
			$data = (object) [
				'css_code'  => $this->css_code,
				'css_files' => $this->css_files,
				'css_urls'  => $this->css_urls,
				'js_code'   => $this->js_code,
				'js_files'  => $this->js_files,
				'js_urls'   => $this->js_urls,
			];
		//	$data = apply_filters('assets_before_replacements', $data, $html);

		//	Multidimensional is a problem
		//	$this->css_vars  = array_unique($this->css_vars);	// makes no sense as they have key so there is no duplicate
		//	$this->css_files = array_unique($this->css_files);
		//	$this->css_urls  = array_unique($this->css_urls);
		//	$this->js_vars   = array_unique($this->js_vars);	// makes no sense as they have key so there is no duplicate
		//	$this->js_files  = array_unique($this->js_files);
		//	$this->js_urls   = array_unique($this->js_urls);

			$styles  = '';
			$scripts = '';

			if(!empty($this->css_vars)) {
				if(IS_DEV) $styles .= '<!-- CSS vars -->';
				$styles .= '<style>'.PHP_EOL.':root {'.PHP_EOL;
				foreach($this->css_vars as $var=>$val) {
					$styles .= $var.': '.$val.';'.PHP_EOL;
				}
				$styles .= '}'.PHP_EOL.'</style>'.PHP_EOL;
			}
			foreach($this->css_code  as $css_code) {
				foreach($css_code  as $code) {
					if(!empty($code)) {
						if(IS_DEV) $styles .= '<!-- CSS code -->';
						$styles  .= '<style>'.PHP_EOL.$code.PHP_EOL.'</style>'.PHP_EOL;
					}
				}
			}
			foreach($this->css_files as $css_files) {
				$css_files = array_unique($css_files);
				foreach($css_files as $file) {
					if(!empty($file)) {
						$path = $this->get_file($file);
						if(!empty($path)) {
							$temp = file_get_contents($path);
							if(!empty($temp)) {
								if(IS_DEV) $styles .= '<!-- CSS file '.$file.' -->';
								$styles .= '<style>'.PHP_EOL.$temp.PHP_EOL.'</style>'.PHP_EOL;
							}
						}
					}
				}
			}
			foreach($this->css_urls  as $css_urls) {
				foreach($css_urls  as $url) {
					if(!empty($url)) {
						$styles .= '<link rel="stylesheet" href="'.$url.'" />'.PHP_EOL;
					}
				}
			}

			if(!empty($this->js_vars)) {
				if(IS_DEV) $scripts .= '<!-- JS vars -->';
				$scripts .= '<script>'.PHP_EOL;
				foreach($this->js_vars as $var=>$val) {
					if(is_bool($val))    $scripts .= 'var '.$var.' = '.($val ? 'true' : 'false').';'.PHP_EOL;
					if(is_numeric($val)) $scripts .= 'var '.$var.' = '.$val.';'.PHP_EOL;
					if(is_object($val))  $scripts .= 'var '.$var.' = '.json_encode($val).';'.PHP_EOL;
					if(is_array($val))   $scripts .= 'var '.$var.' = '.json_encode($val).';'.PHP_EOL;
					if(is_string($val))  $scripts .= 'var '.$var.' = \''.$val.'\';'.PHP_EOL;
				}
				$scripts .= '</script>'.PHP_EOL;
			}
			foreach($this->js_code  as $js_code) {
				foreach($js_code  as $code) {
					if(!empty($code)) {
						if(IS_DEV) $scripts .= '<!-- JS code -->';
						$scripts .= '<script>'.PHP_EOL.$code.PHP_EOL.'</script>'.PHP_EOL;
					}
				}
			}
			foreach($this->js_files as $js_files) {
				$js_files = array_unique($js_files);
				foreach($js_files as $file) {
					if(!empty($file)) {
						$path = $this->get_file($file);
						if(!empty($path)) {
							$temp = file_get_contents($path);
							if(!empty($temp)) {
								if(IS_DEV) $scripts .= '<!-- JS file '.$file.' -->';
								$scripts .= '<script>'.PHP_EOL.$temp.PHP_EOL.'</script>'.PHP_EOL;
							}
						}
					}
				}
			}
			foreach($this->js_urls  as $js_urls) {
				foreach($js_urls  as $url) {
					if(!empty($url)) {
						$scripts .= '<script src="'.$url.'" defer></script>'.PHP_EOL;
					}
				}
			}

			$this->log('Lets go');
		//	$styles  = \Arrakis\Assets::minify_css($styles);
		//	$scripts = \Arrakis\Assets::minify_js($scripts);
			$html = str_replace('<!-- SusiPlate header -->', $styles,  $html);
			$html = str_replace('<!-- SusiPlate footer -->', $scripts, $html);
		//	$html = \Arrakis\Assets::minify_html($html);
		//	$html = apply_filters('assets_generated_html', $html);
			return trim($html);
		}

		function get_file($file) {
			// TODO use registered PATHs
			if($file[0]==='/') {
				if(file_exists($file)) return $file;
				error_log('Not found by Assets «'.$file.'»');
				return null;
			}
			foreach($this->paths as $paths) {
				foreach($paths as $path) {
					$this->log('...trying '.$file.' at '.$path.' : '.(file_exists($path.$file) ? 'sí' : 'no'));
					if(file_exists($path.$file)) return $path.$file;
				}
			}
			error_log('Not found by Assets «'.$file.'» at: '.print_r($this->paths, true));
			return null;
		}

	}
