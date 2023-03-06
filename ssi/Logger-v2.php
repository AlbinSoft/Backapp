<?

	namespace EngineFwk;

	include('EnginePaths.php');

	class Logger {
		use \EngineFwk\EnginePaths;

		static $logs   = [];
		static $tree   = [];
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
			if(class_exists('\Arrakis\Assets')) {
				$assets = \Arrakis\Assets::getInstance();
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

/*		TODO: aislar de WP
		static function error($code, $message, $trace=null) {
			$config  = \Arrakis\Config::getInstance();
			$url     = $config->elog_url ?? 'https://elog.albinsoft.es/';
			$website = $_SERVER['HTTP_HOST'];
			$resp    = wp_remote_post($url, [
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => [],
				'body'        => [
					'website' => $website,
					'code'    => $code,
					'message' => $message,
					'trace'   => $trace,
				],
				'cookies'     => []
			]);
			if(is_wp_error($resp)) {
				$elog = self::get('elog');
				$elog->line($resp->get_error_message());
			} else {
				$elog = self::get('elog');
				$elog->line($resp);
			}
		}
*/

		protected $path     = null;
		protected $virgin   = null;

		protected $depth    = 0;
		protected $display  = false;
		protected $disabled = false;
		protected $idle     = true;	// still not used > no need to write
		private   $lws      = false;	// LastWasString
		protected $memlog   = '';

		protected function __construct($params) {
			$name = $depth = $reset = $config = null;
			extract($params, EXTR_IF_EXISTS);
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
			if($this->depth!==0) return; // TODO Throw an Exception? Call parent? (it's not being saved)
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
				$path_bak = pathinfo($this->path, PATHINFO_DIRNAME).'/'.pathinfo($this->path, PATHINFO_FILENAME).'-'.date('Ymdhi').'.bak';
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

private function writel($txt) {
	if(!$this->disabled) {
		if(is_string($txt)) $this->write($txt);
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

		public function cp() {
			$stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			$stack = $stack[1] ?? null;
			if(!empty($stack)) {
				if(!empty($stack['class'])) {
					$this->line($stack['class'].$stack['type'].$stack['function'].'   #'.$stack['line'].' @'.basename($stack['file']));
				} else {
					$this->line($stack['function'].'   #'.$stack['line'].' @'.basename($stack['file']));
				}
			}
			return $this;
		}



		public function oline(...$args) {
			$obj    = array_shift($args);
			$obj_id = spl_object_id($obj);
			$obj_cn = get_class($obj);
			return $this->str($obj_cn.'#'.$obj_id)->var(...$args);
		}

		public function linef(...$args) {
			$str = array_shift($args);
			return $this->line(sprintf($str, ...$args));
		}

		public function line(...$args) {
			if(count($args)===1 &&  is_string($args[0])) return $this->str($args[0], true);
			if(count($args)===1 && !is_string($args[0])) return $this->var($args[0]);
			return $this->str(array_shift($args), false)->var(...$args);
		}



		public function str($param, $nl = false) {
			if(is_string($param)) {
				$this->lws = !$nl;
				$dts  = date('Y-m-d H:i:s ');
				$tabs = str_repeat('   ', $this->depth);
				$nl   = $nl ? PHP_EOL : null;
				$line = $dts.$tabs.$param.$nl;
				return $this->write($line);
			} else {
				$this->var($param);
			}
			return $this;
		}



		public function var(...$params) {
			if(!$this->lws) {
				$dts  = date('Y-m-d H:i:s ');
				$tabs = str_repeat('   ', $this->depth);
				$line = $dts.$tabs;
			} else {
				$line = ' ';
			}
			foreach($params as $param) {
				$p = null;
				if(is_object($param))   $info = '(object) '      . print_r($param, true);
				if(is_array($param))    $info = '(array) '       . print_r($param, true);
				if(is_string($param))   $info = '(string) '      . $param;
				if(is_bool($param))     $info = '(bool) '        . ($param ? 'true' : 'false');
				if(is_integer($param))  $info = '(int) '         . $param;
				if(is_float($param))    $info = '(float) '       . $param;
				if(is_resource($param)) $info = '(is_resource) ' . $param;
				if($param===null)       $info = '(null)';
				$line .= trim($info, PHP_EOL).' ';
			}
			return $this->write($line.PHP_EOL);
		}



		public function array($a) {
			foreach($a as $k=>$v) {
				$d  = '                    ';
				$t  = str_repeat('   ', $this->depth);
				$s  = "[$k] $v";
				file_put_contents($this->path, $d.$t.$s.PHP_EOL, FILE_APPEND);
				if($this->display) $this->memlog .= $d.$t.$s.PHP_EOL;
			}
			return $this;
		}

		public function csv($a, $nl = false) {
			if(is_array($a)) {
				$this->lws = !$nl;
				$ln = '';
				$d  = date('Y-m-d H:i:s ');  // TODO only if it's start of line
				$t  = str_repeat('   ', $this->depth);
				if(is_array(reset($a))) {
					$ln = PHP_EOL;
					$c = []; foreach($a as $e=>$b) $c[] = $e.' | '.implode(chr(44).chr(32), $b);
					$p  = '(csv) '.implode(PHP_EOL.$d.$t.'      ', $c);
					$nl = true;
					$this->lws = false;
				} else {
					$p  = '(csv) '.implode(chr(44).chr(32), $a);
				}
				$nl = $nl ? PHP_EOL : null;
				$l  = $ln.$d.$t.$p.$nl;
				return $this->write($l);
			} else {
				$this->var($s);
			}
			return $this;
		}

		public function qs($a, $nl = false) {
			if(is_array($a)) {
				$this->lws = !$nl;
				$ln = '';
				$d  = date('Y-m-d H:i:s ');  // TODO only if it's start of line
				$t  = str_repeat('   ', $this->depth);
				if(is_array(reset($a))) {
					$ln = PHP_EOL;
					$c = []; foreach($a as $e=>$b) $c[] = $e.' | '.http_build_query($b);
					$p  = '(qs) '.implode(PHP_EOL.$d.$t.'     ', $c);
					$nl = true;
					$this->lws = false;
				} else {
					$p  = '(qs) '.http_build_query($a);
				}
				$nl = $nl ? PHP_EOL : null;
				$l  = $ln.$d.$t.$p.$nl;
				return $this->write($l);
			} else {
				$this->var($s);
			}
			return $this;
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


/*
		public function table($s, $zztop) {
			$d = date('Y-m-d H:i:s ');
			$t = str_repeat('   ', $this->depth);
			$table = $this->arraytotable($zztop);
			$l = trim($d.$s.': '.PHP_EOL.$table, PHP_EOL).PHP_EOL;
			file_put_contents($this->path, $l, FILE_APPEND);
			if($this->display) $this->memlog[] = $l;
			return $this;
		}

		public function arraytotable($zztop) {
			$rows   = json_decode(json_encode($zztop), true);
			$ncols  = count($rows[0]);
			$cols   = array_keys($rows[0]);
			$widths = array_fill(0, $ncols, 0);
			foreach($rows as &$row) {
				foreach($row as $k=>&$v) {
					$v = $this->arraytostring($v);
					unset($v);
				}
				unset($row);
			}
			foreach($rows as $row) {
				$idx = 0;
				foreach($row as $k=>&$v) {
					$widths[$idx++] = max(strlen($k), strlen($v));
				}
			}
			$table  = '';
			$table .= '| ';
			foreach($cols as $i=>$col) {
				$table .= ''.str_pad($col, $widths[$i]).' | ';
			}
			$table = substr($table, 0, -3);
			$table .= ' |'.PHP_EOL;
			foreach($rows as $row) {
				$table .= '| ';
				$idx = 0;
				foreach($row as $i=>$col) {
					$table .= ''.str_pad($col, $widths[$idx++]).' | ';
				}
				$table = substr($table, 0, -3);
				$table .= ' |'.PHP_EOL;
			}
			return $table;
		}

		private function arraytostring($a, $d = 0) {
			$str = '';
			if(is_array($a)) {
				foreach($a as $c=>$b) {
					if(is_array($b)) {
						$str .= $this->arraytostring($b, $d+1);
					} else {
						$str .= str_repeat(' ', $d*2).'['.$c.'] '.$b.' '; // .PHP_EOL;
					}
				}
			} else {
				$str .= str_repeat(' ', $d*2).$a; // .PHP_EOL;
			}
			return $str;
		}
*/
	}
