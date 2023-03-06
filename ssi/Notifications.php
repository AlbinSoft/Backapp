<?php

	namespace EngineFwk;

	class Notifications {

		const SUCCESS = 1;
		const FAILURE = 2;
		const WARNING = 4;
		const ERROR   = 8;

		private $s = [];
		private $f = [];
		private $w = [];
		private $e = [];
		private $p = null;

		public function success($msg) {
			$this->s[] = $msg;
		}

		public function failure($msg) {
			$this->f[] = $msg;
		}

		public function warning($msg) {
			$this->w[] = $msg;
		}

		public function error($msg) {
			$this->e[] = $msg;
		}

		public function has_success() {
			return !empty($this->s);
		}

		public function has_failure() {
			return !empty($this->f);
		}

		public function has_warning() {
			return !empty($this->w);
		}

		public function has_error() {
			return !empty($this->e);
		}

		public function something_wrong() {
			return !empty($this->f) ||  !empty($this->w) || !empty($this->e);
		}

		public function nothing_wrong() {
			return empty($this->f) &&  empty($this->w) && empty($this->e);
		}

		public function what_was_wrong() {
			return implode(PHP_EOL, array_merge($this->f, $this->w, $this->e));
		}

		public function prompt($msg, $opts) {
			$this->p = (object) [
				'msg'  => $msg,
				'opts' => $opts,
			];
		}

		public function render($filter = false) {
			$rr  = $_GET['rr']  ?? null;
			$ad  = $_GET['ad']  ?? null;
			$adn = $_GET['adn'] ?? null;
			$adn = empty($adn) || \EngineFwk\Nonce::instance()->verify('ad', $adn);
			if($rr!==null && !empty($rr)) {
				?><ul class="alert error close" data-autoclose="5"><li><?=$rr ?></li></ul><?php
			}
			if(!empty($ad) && $adn===TRUE) {
				?><ul class="alert success close" data-autoclose="5"><li><?=$ad ?></li></ul><?php
			}
			if($filter===false || $filter^self::SUCCESS) {
				if(!empty($this->s)) {
					?><ul class="alert success close" data-autoclose="5"><?php
						foreach($this->s as $m) {
							?><li><?=$m ?></li><?php
						}
					?></ul><?php
				}
			}
			if($filter===false || $filter^self::FAILURE) {
				if(!empty($this->f)) {
					?><ul class="alert failure close"><?php
						foreach($this->f as $m) {
							?><li><?=$m ?></li><?php
						}
					?></ul><?php
				}
			}
			if($filter===false || $filter^self::WARNING) {
				if(!empty($this->w)) {
					?><ul class="alert warning close"><?php
						foreach($this->w as $m) {
							?><li><?=$m ?></li><?php
						}
					?></ul><?php
				}
			}
			if($filter===false || $filter^self::ERROR) {
				if(!empty($this->e)) {
					?><ul class="alert error close"><?php
						foreach($this->e as $m) {
							?><li><?=$m ?></li><?php
						}
					?></ul><?php
				}
			}
			if(!empty($this->p)) {
				?><p class="alert prompt"><?php
					?><span class="msg"><?=$this->p->msg ?></span><?php
					?><span class="opts"><?php
					foreach($this->p->opts as $lbl=>$url) {
						?><a href="<?=$url ?>"><?=$lbl ?></a><?php
					}
					?></span><?php
				?></p><?php
			}
		}

	}