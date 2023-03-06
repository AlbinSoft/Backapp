<?

	namespace EngineFwk;

	class Prozex extends Logger {

		const VERBOSE_TOP       = 1;
	//	const VERBOSE_SUBS      = 2;
	//	const VERBOSE_WARNNINGS = 4;
	//	const VERBOSE_ERRORS    = 8;
	//	const VERBOSE_FATALS    = 16;
		const VERBOSE_FAILED    = 32; // failed are always displayed but not always the details
		const VERBOSE_ALL       = 1+2+4+8+16+32;

		static $instances = [];

		static function get($name, $title = null) {
			if(!isset(self::$instances[$name])) {
				self::$instances[$name] = new Prozex($name, $title, 0);
			}
			return self::$instances[$name];
		}

	//	private $name     = null;	// in parent
	//	private $depth    = 0;		// in parent
		private $title    = null;

		private $subs     = [];
		private $warnings = [];
		private $errors   = [];
		private $fatal    = null;
		private $stopped  = false;

		private function __construct($name, $title, $depth, $parent = null) {
			parent::__construct($name, $depth);
		//	parent::set_parent($parent);
			$this->title  = $title;
			$this->parent = $parent;
		}

		public function get_title() {
			return $this->title ?? $this->name;
		}

		public function add_warning($line) {		// alerta
			$this->line('[warning] '.$line);
			$this->warnings[] = $line;
			return $this;
		}

		public function has_warnings() {
			return !empty($this->warnings);
		}

		public function get_warnings() {
			return $this->warnings;
		}

		public function reset_warnings() {
			$this->warnings = [];
			return $this;
		}

		public function add_error($line) {		// alerta
			if(is_array($line)) {
				foreach($line as $l) {
					$this->line('[error] '.$l);
					$this->errors[] = $l;
				}
			} else {
				$this->line('[error] '.$line);
				$this->errors[] = $line;
			}
			return $this;
		}

		public function has_errors() {
			return !empty($this->errors);
		}

		public function get_errors() {
			return $this->errors;
		}

		public function reset_errors() {
			$this->errors = [];
			return $this;
		}

		public function set_fatal($line) {		// detiene el proceso
			$this->line('[fatal] '.$line);
			$this->fatal = $line;
			$this->stop();
			return $this;
		}

		public function is_fatal() {
			return !empty($this->fatal);
		}

		public function get_fatal() {
			return $this->fatal;
		}

		public function reset_fatal() {
			$this->fatal = null;
			$this->stopped = false;
			return $this;
		}

		public function reset() {
			$this->warnings = [];
			$this->errors   = [];
			$this->fatal    = null;
			$this->stopped  = false;
			return $this;
		}

		public function ami_ok() {
			$ko = $this->has_warnings() || $this->has_errors() || $this->is_fatal();
			return !$ko;
		}

		public function went_ok() {
			$ok = $this->ami_ok();
			$sub = reset($this->subs);
			while($ok && $sub) {
				$ok = $ok && $sub->went_ok();
				$sub = next($this->subs);
			}
			return $ok;
		}

		public function get_log($vl = self::VERBOSE_ALL) {
		//	$rv     = 'get_log'.PHP_EOL;
			$rv     = '';
			$ok     = $this->went_ok();
			$tabs   = str_repeat('   ', $this->depth);
			$nltab  = str_repeat('   ', $this->depth); // PHP_EOL.
			$iamtop = ($this->parent===null);
			$vtitle = ($vl & self::VERBOSE_TOP and $iamtop) || ($vl == self::VERBOSE_ALL);
/*
$rv .= $tabs.$vl.'top? '.((int) $vl & self::VERBOSE_TOP and $iamtop).PHP_EOL;
$rv .= $tabs.$vl.'all? '.((int) $vl == self::VERBOSE_ALL).PHP_EOL;
echo "($vl & self::VERBOSE_TOP and $iamtop)".PHP_EOL;
var_dump($vl, $vl & self::VERBOSE_TOP, $iamtop);
echo "($vl & self::VERBOSE_ALL)".PHP_EOL;
var_dump($vl & self::VERBOSE_ALL);
$rv .= "|{$this->title}|{$vtitle}|{$ok}|".PHP_EOL;
var_dump($vtitle);
*/
			if($vtitle || !$ok) {
				if($ok) {
					$rv .= $tabs.$this->get_title().' ... OK'.PHP_EOL;
				} else {
					$rv .= $tabs.$this->get_title().' ... <span style="color: red;">failed</span>'.PHP_EOL;
				}
			}
			if($this->has_warnings() && ($vl==self::VERBOSE_FAILED)) {
				$msgs  = array_map(function ($msg) use ($tabs) {
					return $tabs.'   <span style="color: red;">warning:</span> '.$msg.PHP_EOL;
				}, $this->warnings);
				$rv   .= implode($ttabs, $msgs);
			}
			if($this->has_errors() && ($vl==self::VERBOSE_FAILED)) {
				$msgs  = array_map(function ($msg) use ($tabs) {
					return $tabs.'   <span style="color: red;">warning:</span> '.$msg.PHP_EOL;
				}, $this->errors);
				$rv   .= implode($ttabs, $msgs);
			}
			if($this->is_fatal() && ($vl==self::VERBOSE_FAILED)) {
				$ttabs = $tabs.'   <span style="color: red;">fatal:</span> ';
				$rv   .= $ttabs.$this->fatal.PHP_EOL;
			}
			foreach($this->subs as $sub) {
				$rv .= $sub->get_log($vl);
			}
			return $rv;
		}

		// por defecto se para toda la cadena de procesos
		// si se indica $name se paran los procesos hijos del indicado
		private function stop($name = true) {
			$this->stopped = $name;
			if($this->parent!==null && ($name===true || $this->name!==$name))
				$this->parent->stop($name);
		}

		private function muststop($name = true) {
			return ($this->stopped===true || $this->stopped===$name);
			return $this->parent!==null ? $this->parent->muststop() : ($this->stopped===true || $this->stopped===$name);
		}

		function sub($title = null) {
			if(!empty($title)) $this->line($title.' »');
			$name = md5($title);
			if(!isset($this->subs[$name])) {
				$sub = new Prozex($name, $title, $this->depth+1, $this);
				$sub->path    = $this->path;
				$sub->display = $this->display;
				$sub->memlog  = &$this->memlog;
				$sub->parent  = $this;
				$this->subs[$name] = $sub;
			}
			return $this->subs[$name];
		}

	}
