<?php

	namespace EngineFwk;

	class Nonce {

		static private $instance;

		static public function instance() {
			if(empty(self::$instance))
				self::$instance = new Nonce();
			return self::$instance;
		}

		private static $nonces;

		private function __construct() {
		//	!empty(session_id()) || start_session();
			$this->nonces = $_SESSION['nonces'] ?? [];
			if(!is_array($this->nonces)) $this->nonces = [];
			$this->logpath = PATH_LOGS.'nonce.log';
			$this->log("Constructed ".session_id());
		}

		private function log($line) {
			if(is_array($line))  $line = json_encode($line);
			if(is_object($line)) $line = json_encode($line);
			file_put_contents($this->logpath, trim($line, PHP_EOL).PHP_EOL, FILE_APPENDER);
		//	echo trim($line, PHP_EOL).PHP_EOL;
		}

		public function create($key) {
			$this->nonces[$key] = uniqid();
			$this->log("Created {$key} with value {$this->nonces[$key]}");
			$this->save();
			return $this->nonces[$key];
		}

		public function verify($key, $value) {
			if(isset($this->nonces[$key]) && $this->nonces[$key]==$value) {
				$this->log("Verification {$key} ({$value}) succeded");
				unset($this->nonces[$key]);
				$this->save();
				return true;
			}
			$this->log("Verification $key ($value) failed");
			return false;
		}

		private function save() {
			$_SESSION['nonces'] = $this->nonces;
			$this->log("Saved".print_r($this->nonces, true));
		}

	}