<?

	namespace Arrakis;
	use \DateTime     as DateTime;
	use \DateTimeZone as DateTimeZone;

	define('RURI', $_SERVER['REQUEST_URI']);

	//	Server-Timing: miss, db;dur=53, app;dur=47.2
	//	http://sandbox.onlinephpfunctions.com/code/ff96a5ff62159b9819e1a8675bc7de02b421e84c
	// 1         second
	// 0 001     millisecond
	// 0 001 001 microsecond µs chr(230)

	class ServerTiming {

		static $instance;
		static $reset_dt;
		static $reset_ts;

		static public function get() {
			if(empty(self::$instance)) {
				self::$instance = new ServerTiming();
			}
			if(empty(self::$reset_ts)) {
				self::$reset_dt = DateTime::createFromFormat('0.u00 U', microtime(), new DateTimeZone('Europe/Berlin'));
				self::$reset_ts = self::$reset_dt->modify('-1 hour')->format('U');
			}
			return self::$instance;
		}

		private $events;

		private function __construct() {
			$this->events = [];
			$this->timers = [];
		}

		public function timer($event) {
			if(empty($this->timers[$event])) {
				$this->timers[$event] = new ServerTimingTimer();
			}
			return $this->timers[$event];
		}

		public function start($event) {
			$this->events[$event] = $this->events[$event] ?? ['dts'=>'', 'starts'=>'', 'stops'=>'', 'took'=>''];
			$this->events[$event]['dts']    = date('Y-m-d H:i:s');
		//	$this->events[$event]['starts'] = self::millitime();
			$this->events[$event]['starts'] = self::microtime();
//			$this->logger->line('created '.$event)->var(http_build_query($this->events[$event]));
			return $this;
		}

		public function stop($event) {
			if(empty($this->events[$event])) {
//				$this->logger->line('Unknown event '.$event.' (not stopped)');
			} elseif(empty($this->events[$event])) {
//				$this->logger->line('Already stopped '.$event.'');
			} else {
			//	$this->events[$event]['stops'] = self::millitime();
				$this->events[$event]['stops'] = self::microtime();
				$this->events[$event]['took']  = (int) ($this->events[$event]['stops'] - $this->events[$event]['starts']);
//				$this->logger->line('stopped '.$event)->var(http_build_query($this->events[$event]));
			}
			return $this;
		}

		public function send() {
			if(!empty($this->events)) {
				$h = 'Server-Timing: ';
				foreach($this->events as $event=>&$times) {
					if(empty($times['stops'])) {
						$this->stop($event);
					}
					$time = $times['took'] / 1000;
					$h .= $event.';dur='.$time.', ';
					unset($times);
				}
				foreach($this->timers as $event=>&$timer) {
					$time = $timer->took() / 1000;
					$h .= $event.';dur='.$time.', ';
					unset($timer);
				}
				$h = trim($h, ', ');
//				$this->logger->line('send', $h);
				header($h);
			}
		}

		public function dump() {
			if(!empty($this->events) || !empty($this->timers)) {
				$h = '';
				foreach($this->events as $event=>&$times) {
					if(empty($times['stops'])) {
						$this->stop($event);
					}
					$time = $times['took'];
					$h .= $event.' = '.$time.' µs; ';
					unset($times);
				}
				foreach($this->timers as $event=>&$timer) {
					$time = $timer->took();
					$h .= $event.' = '.$time.' µs; ';
					unset($timer);
				}
				$h = trim($h, '; ');
//				$this->logger->line('send', $h);
				return $h;
			}
		}

		public function mt() {
			$dt = DateTime::createFromFormat('0.u00 U', microtime(), new DateTimeZone('Europe/Berlin'));
			$s  = intval($dt->format('U'));
			$us = intval($dt->format('u'));
			$s -= self::$reset_ts;
			$s *= 1000*1000;
			$mt = $s + $us;
			return $mt;
		}

		static public function microtime() {
			$st = self::get();
			return $st->mt();
		}

		static public function millitime() {
			$st = self::get();
			return $st->mt() / 1000;
		}

	}

	class ServerTimingTimer {

		private $start = null;
		private $stop  = null;
		private $tooks = [];

		public function __construct() {
		}

		public function start() {
			if($this->start!==null) {
				throw(new \Exception('Timer already started'));
			}
			$this->start = ServerTiming::microtime();
			$this->stop  = null;
			return $this;
		}

		public function stop() {
			if($this->stop!==null) {
				throw(new \Exception('Timer already stopped'));
			}
			$this->stop  = ServerTiming::microtime();
			$this->tooks[] = $this->stop - $this->start;
			$this->start = null;
			return $this;
		}

		public function took() {
			return (int) array_sum($this->tooks);
		}

	}