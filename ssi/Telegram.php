<?php

	namespace EngineFwk;

	class Telegram {

		static private $instance;

		static public function instance() {
			if(empty(self::$instance))
				self::$instance = new Telegram();
			return self::$instance;
		}

		public function send_message($to, $text) {
			$log = PATH_ROOT.'telegram.log';
			try {
				file_put_contents($log, date('Y-m-d H:i:s').' Sending to '.$to.': '.$text.PHP_EOL, FILE_APPEND);
				$url = "https://albinsoft.es/tgram/sendmessage.php?to=".urlencode($to)."&m=".urlencode($text);
				if($ch = \curl_init($url)) {
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					$rs = curl_exec($ch);
					$in = curl_getinfo($ch);
					curl_close($ch);
					file_put_contents($log, print_r($rs, true).PHP_EOL, FILE_APPEND);
					file_put_contents($log, print_r($in, true).PHP_EOL, FILE_APPEND);
					$ok = ($in['http_code']==200);
					if($ok) {
						return true;
					} else {
						file_put_contents($log, 'sendTG#2:'.print_r($rs, true).PHP_EOL, FILE_APPEND);
						return false;
					}
				}
			} catch(Exception $ex) {
				file_put_contents($log, 'sendTG#1:'.print_r($ex, true).PHP_EOL, FILE_APPEND);
				return false;
			}
		}

	}
