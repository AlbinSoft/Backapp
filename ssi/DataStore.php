<?

	namespace EngineFwk;

	class DataStore {

		static $stores = [];

		static function getInstance($name) { // , $byuser   $name .= session_id();
			if(!isset(self::$stores[$name])) {
				self::$stores[$name] = new DataStore($name);
			}
			return self::$stores[$name];
		}

		private $store = [];

		private function __construct($name) {
			$this->name = $name;
		}

		public function set($key, $val) {
			$this->store[$key] = $val;
		}

		public function get($key, $def = null) {
			return $this->store[$key] ?? $def;
		}

		public function save() {
		//	$path = THEME_PATH.$name.'.bin';
		//	file_put_contents($path, $this->serialize());
			return $this;
		}

		public function load() {
		//	$path = THEME_PATH.$name.'.bin';
		//	$file = file_get_contents($paht);
		//	$this->unserialize($file);
			return $this;
		}

		public function serialize() {
			return serialize($store);
		}

		public function unserialize($store) {
			if(!empty($store)) {
				$this->store = unserialize($store);
			}
			return $this;
		}

	}
