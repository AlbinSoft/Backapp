<?

	namespace EngineFwk;

	class AppHooks {
		use hooks;

		static private $instance = null;

		static public function instance() {
			if(empty(self::$instance))
				self::$instance = new AppHooks();
			return self::$instance;
		}

		static public function addfilter($name, $cb) {
			return self::instance()->add_filter($name, $cb);
		}

		static public function dofilter($name, $args) {
			return self::instance()->filter($name, $args);
		}

		static public function addaction($name, $cb) {
			return self::instance()->add_action($name, $cb);
		}

		static public function doaction($name, $args = []) {
			self::instance()->do_action($name, $args);
		}

	}

	trait hooks {

		private $hooks = [];

		public function add_filter($name, $cb) {
			$this->hooks[$name]   = $this->hooks[$name] ?? [];
			$this->hooks[$name][] = $cb;
			return $this;
		}

		private function filter($name, $args) {
			if(!is_array($args)) $args = [$args];
			$this->hooks[$name]   = $this->hooks[$name] ?? [];
			if(!empty($this->hooks[$name]) && is_array($this->hooks[$name])) {
				if(!is_array($args)) $args = [$args];
				foreach($this->hooks[$name] as $hook) {
					$args[0] = call_user_func($hook, ...$args);
				}
			}
			return $args[0];
		}

		public function add_action($name, $cb) {
			$this->hooks[$name]   = $this->hooks[$name] ?? [];
			$this->hooks[$name][] = $cb;
			return $this;
		}

		private function do_action($name, $args = []) {
			if(!is_array($args)) $args = [$args];
			$this->hooks[$name]   = $this->hooks[$name] ?? [];
			if(!empty($this->hooks[$name]) && is_array($this->hooks[$name])) {
				foreach($this->hooks[$name] as $hook) {
					call_user_func($hook, ...$args);
				}
			}
		}

	}
