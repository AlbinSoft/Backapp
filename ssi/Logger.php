<?

	namespace EngineFwk;

	require_once('EnginePaths.php');

	class Logger {
		use \EngineFwk\EnginePaths;

		static $logs   = [];
		static $config = null;

		static function get($name, $reset = null) {
			if(self::$config===null) {
				$path = self::getFilePath('als-logger.json');
				if(file_exists($path)) {
					$config = file_get_contents($path);
					$config = json_decode($config);
				}
				if(is_object($config)) {
					self::$config = $config;
				} else {
					self::$config = false;
				}
			}
			if(!isset(self::$logs[$name])) {
				$config = self::$config->$name ?? null;
				$logger = new Logger([
					'name'   => $name,
					'depth'  => 0,
					'reset'  => $reset  ?? $config->reset ?? false,
					'config' => $config ?? null,
				]);
				self::$logs[$name] = $logger;
			}
			return self::$logs[$name];
		}

		static function render() {
			if(class_exists('\EngineFwk\Assets')) {
				$assets = \EngineFwk\Assets::getInstance();
				$assets->add_css_file(15, 'EngineFwk/logger.css');
				$assets->add_js_file (15, 'EngineFwk/logger.js');
			}
			?><div class="logger"><?
				?><p class="logger_tabs"><?
				foreach(self::$logs as $name=>$log) if(!$log->disabled && $log->display) {
					?><span><?=$name ?></span><?
				}
			?></p><?
				foreach(self::$logs as $name=>$log) if(!$log->disabled && $log->display) {
					?><pre class="logger_cont"><?=$log->memlog ?></pre><?
				}
			?></div><?
		}

		static function error($code, $message, $trace=null) {
			$url     = 'https://elog.albinsoft.es/';
			$website = $_SERVER['HTTP_HOST'];
			if(class_exists('\EngineFwk\HTTP')) {
				$resp = \EngineFwk\HTTP::exec([
					'url'          => $url,
					'method'       => 'POST',
					'headers'      => [],
					'payload'      => [
						'website' => $website,
						'code'    => $code,
						'message' => $message,
						'trace'   => $trace,
					],
				]);
			}
			if(empty($resp)) {
				$elog = self::get('elog');
				$elog->line('elog failed');
			} else {
				$elog = self::get('elog');
				$elog->line($resp);
			}
		}

		protected $path     = null;
		protected $virgin   = null;

		protected $depth    = 0;
		protected $display  = false;
		protected $disabled = false;
		protected $idle     = true;		// still not used > no need to write
		private   $otf      = '';		// OnTheFly (still not written)
		protected $memlog   = '';

		protected function __construct($params) {
			$name = $depth = $reset = $config = null;
			if(count($params)>0) {
				if(is_array($params[0])) {
					extract($params[0], EXTR_IF_EXISTS);
				} else {
					$name  = $params[0] ?? '';
					$depth = $params[1] ?? 0;
					$reset = $params[2] ?? false;
				}
			}
			$this->name    = $name;
			$this->depth   = $depth;
			$this->reset   = $reset;
			if($config!==null) {
				if(isset($config->disabled) && $config->disabled!==null) $this->disabled = $config->disabled;
				if(isset($config->display)  && $config->display!==null)  $this->display  = $config->display;
			}
			if($depth===0) {
				$path    = $config->path ?? null;
				$default = self::trailingslash(dirname(__FILE__));
				switch(true) {
					case $path!==null:         $this->set_path($path);     break;
					case defined('PATH_LOGS'): $this->set_path(PATH_LOGS); break;
					case defined('LOGS_PATH'): $this->set_path(LOGS_PATH); break;
					case defined('PATH_ROOT'): $this->set_path(PATH_ROOT); break;
					default:                   $this->set_path($default);  break;
				}
				register_shutdown_function([$this, 'save']);
			}
		}

		public function set_path($path) {
			if($this->depth!==0) throw new \Exception('Sublogs cannot have a different path than its parents');
			if(is_dir($path)) {
				$path = rtrim($path, '/')."/{$this->name}.log";
			}
			$this->path   = $path;
			$this->virgin = !file_exists($path) || filesize($path)===0;
			// TODO rotate logs, by day or by initilization $this->backup()
			if($this->reset && file_exists($path)) unlink($path);
			return $this;
		}

		public function backup() {
		//	if($this->depth!==0) return; // TODO Throw an Exception?
			if(file_exists($this->path)) {
				$path_bak = pathinfo($this->path, PATHINFO_DIRNAME).'/'.pathinfo($this->path, PATHINFO_FILENAME).'-'.date('YmdHi').'.bak';
				rename($this->path, $path_bak);
			}
			return $this;
		}

		public function sub($title = null) {
			if(!empty($title)) $this->line($title.' »');
			$sub = new Logger([
				'name'   => $this->name,
				'depth'  => $this->depth+1,
				'reset'  => false,
				'config' => $this->config,
			]);
			$sub->display  = $this->display;
			$sub->disabled = $this->disabled;
			$sub->idle     = &$this->idle;
			$sub->memlog   = &$this->memlog;
			$sub->parent   = $this;
			return $sub;
		}

		protected function set_parent($parent) {
			$this->path     = $parent->path;
			$this->disabled = $parent->disabled;
			$this->display  = $parent->display;
			$this->memlog   = &$parent->memlog;
		//	$this->parent   = $parent;
			return $this;
		}

		public function display() {
			$this->display = true;
			return $this;
		}

		private function write($str) {
			if(!$this->disabled) {
				$this->idle    = false;
				$this->memlog .= $str;
			}
			return $this;
		}

		private function writeln($txt) {
			if(!$this->disabled && !empty($txt) && (is_string($txt) || is_array($txt))) {
				$dts   = date('Y-m-d H:i:s ');
				$tabs  = str_repeat('   ', $this->depth);
				$pref  = $dts.$tabs;
				$premp = str_repeat(' ', strlen($pref));
				$lines = is_string($txt) ? explode(PHP_EOL, $txt) : $txt;
				foreach($lines as $idx => &$line) {
					if($idx===0) $line = $pref.$line;
					if($idx!==0) $line = $premp.$line;
				}
				$txt = implode(PHP_EOL, $lines).PHP_EOL;
				$this->write($txt);
			}
			return $this;
		}

		public function save() {
			if(!$this->idle) {
				if($this->virgin) {
					file_put_contents($this->path, "\xEF\xBB\xBF");
				}
				file_put_contents($this->path, $this->memlog, FILE_APPEND);
			}
		}

		public function cp($depth = 1) {
			$stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			$stack = $stack[$depth] ?? null;
			if(!empty($stack)) {
				if(!empty($stack['class'])) {
					$this->line('@'.basename($stack['file']).'#'.$stack['line'].' » '.$stack['class'].$stack['type'].$stack['function']);
				} else {
					$this->line('@'.basename($stack['file']).'#'.$stack['line'].' » '.$stack['function']);
				}
			}
			return $this;
		}



		public function oline(...$args) {
			$obj    = array_shift($args);
			$obj_id = spl_object_id($obj);
			$obj_cn = get_class($obj);
			return $this->str($obj_cn.'#'.$obj_id.' ')->var(...$args);
		}

		public function linef(...$args) {
			$str = array_shift($args);
			return $this->line(sprintf($str, ...$args));
		}

		public function line(...$args) {
			if(count($args)===1 &&  is_string($args[0])) return $this->str($args[0], true);
			if(count($args)===1 && !is_string($args[0])) return $this->var($args[0]);
			return $this->str(array_shift($args).chr(32), false)->var(...$args);
		}



		public function str($param, $nl = false) {
			if(is_string($param)) {
				$this->otf .= $param;
				if($nl) {
					$this->writeln($this->otf);
					$this->otf = '';
				}
			} else {
				$this->var($param);
			}
			return $this;
		}



		public function var(...$params) {
			foreach($params as $param) {
				$info = '';
				if(!empty($this->otf))  $info .= $this->otf;
				if(is_object($param))   $info .= '(object) '      . print_r($param, true);
				if(is_array($param))    $info .= '(array) '       . print_r($param, true);
				if(is_string($param))   $info .= '(string) '      . $param;
				if(is_bool($param))     $info .= '(bool) '        . ($param ? 'true' : 'false');
				if(is_integer($param))  $info .= '(int) '         . $param;
				if(is_float($param))    $info .= '(float) '       . $param;
				if(is_resource($param)) $info .= '(is_resource) ' . $param;
				if($param===null)       $info .= '(null)';
				$line .= trim($info, PHP_EOL).' ';
				$this->otf = '';
			}
			return $this->writeln($line);
		}



		public function avar($a) {
			if(is_array($a)) {
				return $this->array($a);
			}
			return $this->var($s);
		}



		public function array($a) {
			foreach($a as $k=>$v) {
				$d  = '                    ';
				$t  = str_repeat('   ', $this->depth);
				$s  = "[$k] $v";
				$this->writeln($d.$t.$s);
			}
			return $this;
		}



		public function csv($param, $nl = false) {
			if(is_array($param)) {
				$lines = [];
				if(is_array(reset($param))) {
					foreach($param as $k=>$v) {
						$lines[] = $k.' | '.implode(chr(44).chr(32), $v);
					}
					array_walk($lines, function(&$line, $idx) { $line = ($idx==0 ? '(csv) ' : '      ').$line; } );
				} else {
					$lines[] = '(csv) '.implode(chr(44).chr(32), $param);
				}
				return $this->writeln($lines);
			}
			return $this->var($s);
		}



		public function qs($param, $nl = false) {
			if(is_array($param)) {
				$lines = [];
				if(is_array(reset($param))) {
					foreach($param as $k=>$v) {
						$lines[] = $k.' | '.http_build_query($v);
					}
					array_walk($lines, function(&$line, $idx) { $line = ($idx==0 ? '(qs) ' : '     ').$line; } );
				} else {
					$lines[] = '(qs) '.http_build_query($param);
				}
				return $this->writeln($lines);
			}
			return $this->var($s);
		}

		public function ln() {
			return $this->write(PHP_EOL);
		}

		public function sep() {
			return $this->write(PHP_EOL.PHP_EOL.PHP_EOL);
		}



		public function get_memlog() {
			return $this->memlog;
		}

		public function dump() {
			die('<pre>'.$this->memlog.'</pre>');
			die('<pre>'.implode(PHP_EOL, $this->memlog).'</pre>');
		}

	}
