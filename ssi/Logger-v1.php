<?

	namespace EngineFwk;

	class Logger {

		static $logs = [];
		static $config = null;

		static function get($name, $reset=false) {
			if(self::$config===null) {
				$path = defined('CHILD_PATH') ? CHILD_PATH.'logger.json' : dirname(__FILE__).'/logger.json';
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
				self::$logs[$name] = new Logger($name, 0, $reset, self::$config->$name ?? null);
			}
			return self::$logs[$name];
		}

		static function render() {
			$assets = \Arrakis\Assets::getInstance();
			$assets->add_css_file(15, 'logger.css');
			$assets->add_js_file(15, 'logger.js');
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

		protected $path     = null;
		protected $virgin   = null;
		protected $memlog   = '';

		protected $depth    = 0;
		protected $display  = false;
		protected $disabled = false;
		private   $lws      = false;   // LastWasString

		protected function __construct($name, $depth = 0, $reset = false, $config = null) {
			$this->name    = $name;
			$this->depth   = $depth;
			$this->reset   = $reset;
			$default       = trailingslashit(dirname(__FILE__));
			if($config!==null) {
				if(isset($config->disabled) && $config->disabled!==null) $this->disabled = $config->disabled;
				if(isset($config->display)  && $config->display!==null)  $this->display  = $config->display;
			}
			if($depth===0) switch(true) {
				case isset($config->path) && $config->path!==null: $this->set_path($config->path, $reset); break;
				case defined('PATH_LOGS'): $this->set_path(PATH_LOGS, $reset); break;
				case defined('LOGS_PATH'): $this->set_path(LOGS_PATH, $reset); break;
				case defined('PATH_ROOT'): $this->set_path(PATH_ROOT, $reset); break;
				default:                   $this->set_path($default,  $reset); break;
			}
		}

		protected function set_parent($parent) {
			$this->path     = $parent->path;
			$this->disabled = $parent->disabled;
			$this->display  = $parent->display;
			$this->memlog   = &$parent->memlog;
		//	$this->parent   = $parent;
			return $this;
		}

		public function set_path($path) {
		//	if($this->depth!==0) return; // TODO Throw an Exception?
			if(is_dir($path)) {
				$path = rtrim($path, '/')."/{$this->name}.log";
			}
			$this->path   = $path;
			$this->virgin = !file_exists($path) || filesize($path)===0;
			if($this->reset && $this->depth===0 && file_exists($path)) unlink($path);
		//	if(touch($path) && is_writable($path)) {
		//		if($this->reset && $this->depth===0 && file_exists($path)) unlink($path);
		//		$this->path = $path;
		//		$this->bom();
		//	}
			return $this;
		}

		private function smirch() {
			if($this->virgin) {
				$this->bom();
				$this->virgin = false;
			}
		}

		public function backup() {
		//	if($this->depth!==0) return; // TODO Throw an Exception?
			if(file_exists($this->path)) {
				$path_bak = pathinfo($this->path, PATHINFO_DIRNAME).'/'.pathinfo($this->path, PATHINFO_FILENAME).'-'.date('Ymdhi').'.bak';
				rename($this->path, $path_bak);
		//		$this->bom();
			}
			return $this;
		}

		private function bom() {
			if(!file_exists($this->path) || filesize($this->path)===0) {
				file_put_contents($this->path, "\xEF\xBB\xBF");
			}
		}

		public function display() {
			$this->display = true;
			return $this;
		}

		private function write($str) {
			if(!$this->disabled) {
				$this->smirch();
				file_put_contents($this->path, $str, FILE_APPEND);
				if($this->display) $this->memlog .= $str;
			}
			return $this;
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
			$obj = array_shift($args);
		//	array_unshift($args, spl_object_id($obj));
		//	array_unshift($args, get_class($obj));
			return $this->str(get_class($obj).'#'.spl_object_id($obj))->var(...$args);
			return $this->line(...$args);
		}

		public function linef(...$args) {
			$str = array_shift($args);
			return $this->line(sprintf($str, ...$args));
		}

		public function line(...$args) {
			if(count($args)===1 && is_string($args[0]))  return $this->str($args[0], true);
			if(count($args)===1 && !is_string($args[0])) return $this->var($args[0]);
			return $this->str(array_shift($args), false)->var(...$args);
		}

		public function var(...$oo) {
		//	if(!is_array($oo)) $oo = [$oo];
			if(!$this->lws) {
				$d = date('Y-m-d H:i:s ');
				$t = str_repeat('   ', $this->depth);
				$l = $d.$t;
			} else {
				$l = ' ';
			}
			foreach($oo as $o) {
				$p = null;
				if(is_object($o))   $p = '(object) '      . print_r($o, true);
				if(is_array($o))    $p = '(array) '       . print_r($o, true);
				if(is_string($o))   $p = '(string) '      . $o;
				if(is_bool($o))     $p = '(bool) '        . ($o ? 'true' : 'false');
				if(is_integer($o))  $p = '(int) '         . $o;
				if(is_float($o))    $p = '(float) '       . $o;
				if(is_resource($o)) $p = '(is_resource) ' . $o;
				if($o===null)       $p = '(null)';
				$l .= trim($p, PHP_EOL).' ';
			}
			return $this->write($l.PHP_EOL);
		}

		public function str($s, $nl = false) {
			if(is_string($s)) {
				$this->lws = !$nl;
				$d  = date('Y-m-d H:i:s ');
				$t  = str_repeat('   ', $this->depth);
				$nl = $nl ? PHP_EOL : null;
				$l  = $d.$t.$s.$nl;
				return $this->write($l);
			} else {
				$this->var($s);
			}
			return $this;
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



		public function sub($title = null) {
			if(!empty($title)) $this->line($title.' »');
			$sub = new Logger($this->name, $this->depth+1, false);
			$sub->display = $this->display;
			$sub->memlog  = &$this->memlog;
			$sub->parent  = $this;
			return $sub->set_path($this->path);
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
