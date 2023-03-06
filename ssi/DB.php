<?

	namespace EngineFwk;

	require_once('EnginePaths.php');
	include_once('Logger.php');

	!defined('SERVER_IP') && define('SERVER_IP', $_SERVER['SERVER_ADDR']);

	class DB extends \stdClass {
		use \EngineFwk\EnginePaths;

		static private $configs   = null;
		static private $instances = [];

		static public function get($conf_name = false) {
			return self::getInstance($conf_name);
		}

		static public function getInstance($conf_name = false) {
			if(empty(self::$configs)) {
				$path = self::getFilePath(['als-db.json', 'als-db.cfg']);
				if(!empty($path)) {
					self::$configs = (array) json_decode(file_get_contents($path));
				}
				if(self::$configs==null) {
					die('[AlsDB] Unable to load configuration at «'.$path.'».');
				}
			}

			$conf = null;
			switch(true) {
				case !empty($conf_name) && is_object(self::$configs[$conf_name]):
					$conf = $conf_name;
					break;
				case is_object(self::$configs[SERVER_IP]):
					$conf = SERVER_IP;
					break;
				case is_object(self::$configs['default']):
					$conf = 'default';
					break;
				default:
					die('[AlsDB] Unable to find a valid configuration («'.$conf_name.'» or  «SERVER_IP» or  «default»)');
			}

			if(!empty(self::$instances[$conf]))
				return self::$instances[$conf];

			self::$instances[$conf] = new DB(self::$configs[$conf]);
			return self::$instances[$conf];
		}

		const DATE_EMPTY = '0000-00-00 00:00:00.000';

		private $cfg  = null;
		private $conn = null;
		private $info = null;

		private $logger      = null;
		private $log_sql     = false;
		private $log_path    = false;
		private $show_errors = false;
		private $last_query  = false;

		private function __construct($cfg = null) {
			$this->cfg = $cfg;
			if(class_exists('\EngineFwk\Logger')) {
				$this->log_name = $cfg->log_name ?? basename($cfg->log_path) ?? 'als-db';
				$this->logger = Logger::get($this->log_name);
				if(is_file($cfg->log_path) || is_dir($cfg->log_path)) {
					$cfg->log_path = realpath($cfg->log_path);
					$this->logger->set_path($cfg->log_path);
				} elseif(!empty($cfg->log_path)) {
					$this->logger->set_path(self::getLogPath($cfg->log_path));
				}
			} elseif(!empty($cfg->log_path)) {
				if(!$this->log_path($this->cfg->log_path)) {
					// throw Exception ???
				}
			}
			if(($cfg->log_on ?? false)!==false) {
				$this->log_on();
			} else {
				$this->log_off();
			}
			if(($cfg->show_errors ?? false)!==false) {
				$this->show_errors();
			} else {
				$this->hide_errors();
			}
		}

		public function open() {
			$this->conn = new \mysqli($this->cfg->host, $this->cfg->user, $this->cfg->pass, $this->cfg->name);
			if(!is_object($this->conn))
				die('[AlsDB] Unable to connect to host «'.$this->cfg->host.'» database «'.$this->cfg->name.'» by user «'.$this->cfg->user.'».');
			if($this->conn->connect_errno)
				die('[AlsDB] Unable to connect to host «'.$this->cfg->host.'» database «'.$this->cfg->name.'» by user «'.$this->cfg->user.'» because «'.$this->conn->connect_error.'».');
			if(!$this->conn->set_charset('utf8'))
				die('[AlsDB] Unable to set charset to «UTF-8».');
		//	$this->query('SET GLOBAL group_concat_max_len='.(1024*8));
		}

		public function close() {
			if(is_object($this->conn) && !$this->conn->close())
				die('[AlsDB] Unable to disconnect.');
		}

		public function show_errors()   { $this->show_errors = true; }
		public function hide_errors()   { $this->show_errors = false; }
		public function log_on()        { $this->log_sql = true; }
		public function log_off()       { $this->log_sql = false; }
		public function log_path($path) {
			if(is_file($path) || touch($path)) {
				$this->log_path = $path;
				return true;
			}
			return false;
		}

		private function log_sql($line) {
			if($this->log_sql) {
				if($this->logger) {
					$this->logger->line($line);
				} else {
					if(is_array($line)) {
						$line = implode(';'.PHP_EOL, $line);
					}
					$line = date('Y-m-d H:i:s ').trim($line).PHP_EOL;
					if(!empty($this->log_sql)) {
						file_put_contents($this->log_path, $line, FILE_APPEND);
					}
				}
			}
		}

		private function log_error($line) {
			if($this->show_errors) {
				echo $line.PHP_EOL;
			}
			if($this->log_sql) {
				if($this->logger) {
					$this->logger->line($line);
					$this->logger->cp(3);
				} else {
					$url   = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
					$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
					$trace = $trace[3] ?? $trace[2] ?? $trace[1] ?? $trace[0];
					$trace = $trace['function'].' at line '.$trace['line'].' in file '.$trace['file'];
					$line = date('Y-m-d H:i:s ').trim($url).PHP_EOL.'                    '.trim($trace).PHP_EOL.'                    '.trim($line).PHP_EOL;
					file_put_contents($this->log_path, $line, FILE_APPEND);
				}
			}
		}

		public function get_error() {
			if(empty($this->conn->error)) return false;
			return $this->conn->error;
		}

		public function get_errors() {
			if(empty($this->conn->error_list)) return false;
			return $this->conn->error_list;
		}

		public function get_warns($max = 10) {
			$max = max(1, intval($max));
			return $this->get_rows("SHOW WARNINGS LIMIT $max");
		}

		public function get_connection() {
			return $this->conn;
		}

		public function query($query) {
			if($this->conn===null) $this->open();
			if($this->conn!==null) {
				$this->log_sql($query);
				$mt  = DB::microtime();
				$res = $this->conn->query($query);
				$mt  = DB::microtime() - $mt;
				if($res===false) {
					$this->log_error("[AlsDB:query] Query «".$query."» error «".$this->conn->error."»");
				}
				$this->log_sql('done '.($mt<0.0001 ? '~0' : number_format($mt)).' µs');
				return $res;
			}
		}

		public function queries($queries) {
			if($this->conn===null) $this->open();
			if($this->conn!==null) {
				$this->log_sql($queries);
				if(is_array($queries))
					$queries = implode(';', $queries);
				$mt  = DB::microtime();
				$res = $this->conn->multi_query($queries);
				$mt  = DB::microtime() - $mt;
				$this->log_sql('done '.($mt<0.0001 ? '~0' : number_format($mt)).' µs');
				if($res===false) {
					$this->log_error("[AlsDB:queries] Query «".$query."» error «".$this->conn->error."»");
				} else {
					while($this->conn->more_results()) {
						$res = $this->conn->next_result();
						if($res===false) {
							$this->log_error("[AlsDB:queries] Multiquery error «".$this->conn->error."»");
						} else {
							$res = $this->conn->store_result();
							if($res===false && !empty($this->conn->error)) {
								$this->log_error("[AlsDB:queries] Multiquery error «".$this->conn->error."»");
							}
						}
					}
				}
				return $res;
			}
		}

		public function prepare($query) {
			if($this->conn===null) $this->open();
			if($this->conn!==null) {
				$this->log_sql($query);
				$res = new Stmt($this, $this->conn, $query);
				return $res;
			}
		}

		public function insert_id() {
			if($this->conn===null) $this->open();
			if($this->conn!==null) {
				$res = $this->conn->insert_id;
				$this->log_sql('Insert ID: '.$res);
				return $res;
			}
		}

		public function get_value($query) {
			if($this->conn===null) $this->open();
			if($this->conn!==null) {
				$rset = $this->query($query);
				if($rset!==false) {
					$this->info = $rset->fetch_fields();
					$row = $rset->fetch_array(MYSQLI_NUM);
					$rset->free();
				} else {
					$this->info = null;
					return null;
				}
				return !empty($row) ? $row[0] : null;
			}
			return null;
		}

		public function get_values($query) {
			if($this->conn===null) $this->open();
			if($this->conn!==null) {
				$rset = $this->query($query);
				if($rset!==false) {
					$this->info = $rset->fetch_fields();
					$rows = [];
					while($row = $rset->fetch_array(MYSQLI_NUM)) $rows[] = $row[0];
					$rset->free();
				} else {
					$this->info = null;
					return null;
				}
				return !empty($rows) ? $rows : null;
			}
			return null;
		}

		public function get_row($query, $values = false) {
			if($this->conn===null) $this->open();
			if($this->conn!==null) {
				// TODO if($values===false) {
					$rset = $this->query($query);
				// TODO } else {
					// TODO if($stmt=$mysqli->prepare($query)) {
						// TODO $stmt->bind_param("s", $city);
					// TODO }
				// TODO }
				if($rset!==false) {
					if(!is_bool($rset)) {
						$this->info = $rset->fetch_fields();
						$row = $rset->fetch_array(MYSQLI_ASSOC); /* $rset->num_rows ? */
						$rset->free();
						$this->consume();
						return $row ? (object) $row : NULL;
					} else {
						return null;
					}
				} else {
					$this->info = null;
					return null;
				}
			}
		}

		public function get_rows($query) {
			if($this->conn===null) $this->open();
			if($this->conn!==null) {
				$rset = $this->query($query);
				if($rset!==false && $rset!==null) {
					$this->info = $rset->fetch_fields();
					$rows = [];
					while($row = $rset->fetch_array(MYSQLI_ASSOC)) $rows[] = (object) $row;
					$rset->free();
					$this->consume();
					return $rows;
				} else {
					$this->info = null;
					return null;
				}
			}
		}

		public function get_krows($query, $keyfield = false) {
			if($this->conn===null) $this->open();
			if($this->conn!==null) {
				$rset = $this->query($query);
				if($rset!==false && $rset!==null) {
					$this->info = $rset->fetch_fields();
					if($keyfield===FALSE) {
						$keyfield = $this->info[0]->name;
					}
					$rows = [];
					while($row = $rset->fetch_array(MYSQLI_ASSOC)) {
						$key = $row[$keyfield];
						if(count($row)==2) {
							$rows[$key] = $row[$this->info[1]->name];
						} else {
							$rows[$key] = (object) $row;
						}
					}
					$rset->free();
					return $rows;
				} else {
					$this->info = null;
					return null;
				}
			}
		}

		public function get_karows($query, $keyfield = false) {
			if($this->conn===null) $this->open();
			if($this->conn!==null) {
				$rset = $this->query($query);
				if($rset!==false && $rset!==null) {
					$this->info = $rset->fetch_fields();
					if($keyfield===FALSE) {
						$keyfield = $this->info[0]->name;
					}
					$rows = [];
					while($row = $rset->fetch_array(MYSQLI_ASSOC)) {
						$key = $row[$keyfield];
						$rows[$key] = isset($rows[$key]) ? $rows[$key] : [];
						if(count($row)==2) {
							$rows[$key][] = $row[$this->info[1]->name];
						} else {
							$rows[$key][] = (object) $row;
						}
					}
					$rset->free();
					return $rows;
				} else {
					$this->info = null;
					return null;
				}
			}
		}

		public function get_frows($query, $keyfield = false) {
			if($this->conn===null) $this->open();
			if($this->conn!==null) {
				$rset = $this->query($query);
				if($rset!==false && $rset!==null) {
					$this->info = $rset->fetch_fields();
					if($keyfield===FALSE) {
						$keyfield = $this->info[0]->name;
					}
					$rows = [];
					while($row = $rset->fetch_array(MYSQLI_ASSOC)) {
						$rows[] = $row[$keyfield];
					}
					$rset->free();
					return $rows;
				} else {
					$this->info = null;
					return null;
				}
			}
		}

		public function get_found_rows() {
			return (int) $this->get_value("SELECT FOUND_ROWS()");
		}

		public function get_num_rows() {
			return (int) $this->get_value("SELECT FOUND_ROWS()");
		}

		public function get_info() {
			return $this->info;
		}

		public function get_affected_rows() {
			return $this->conn->affected_rows;
		}

		public function insert($table, $data) {
			if($this->conn===null) $this->open();
			if($this->conn!==null) {
				if(empty($data))
					return true;
				foreach($data as $key=>&$val)
					$val = $val===null ? "null" : "'".$this->escape($val)."'";
				$keys   = array_keys($data);
				$values = array_values($data);
				$query  = 'INSERT INTO '.$table.' (';
				$query .= implode(chr(44), $keys);
				$query .= ') VALUES (';
				$query .= implode(chr(44), $values);
				$query .= ') ON DUPLICATE KEY UPDATE ';
				foreach($data as $key=>&$val)
					$query .= $key.'='.$val.chr(44);
				$query  = trim($query, chr(44));
				$query .= ';';
				if(!$this->query($query)) {
					$this->log_error("[AlsDB:insert] Query «".$query."» error «".$this->conn->error."»");
					return false;
				}
				return $this->conn->affected_rows;
			} else {
				return false;
			}
		}

		public function insert_rel($table, $p_name, $p_value, $c_name, $c_values) {
			if($this->conn===null) $this->open();
			if($this->conn!==null) {
				if(!is_array($c_values)) return false;
				$this->delete($table, [$p_name=>$p_value]);
				$values = [];
				foreach($c_values as $c_value) {
					$values[] = '('.$p_value.', '.$c_value.')';
				}
				$query  = 'INSERT INTO '.$table.' ('.$p_name.', '.$c_name.') ';
				$query .= 'VALUES '.implode(chr(44), $values).';';
				if(!$this->query($query)) {
					$this->log_error("[AlsDB:insert] Query «".$query."» error «".$this->conn->error."»");
					return false;
				}
				return $this->conn->affected_rows;
			} else {
				return false;
			}
		}

		public function insert_bulk($table, $rows, $ignore = false) {
			if($this->conn===null) $this->open();
			if($this->conn!==null) {
				if(!is_array($rows)) return false;
				if(empty($rows))     return false;
			//	$this->delete($table, [$p_name=>$p_value]);
				$fields = array_keys(reset($rows));
				foreach($rows as &$row) {
					foreach($row as &$value) {
						$this->quote($value);
						unset($value);
					}
					$row = '('.implode(chr(44), $row).')';
					unset($row);
				}
				$ignore = $ignore ? 'IGNORE' : '';
				$query  = 'INSERT INTO '.$ignore.' '.$table.' ('.implode(chr(44), $fields).') ';
				$query .= 'VALUES '.implode(chr(44), $rows).';';
				if(!$this->query($query)) {
					$this->log_error("[AlsDB:insert] Query «".$query."» error «".$this->conn->error."»");
					return false;
				}
				return $this->conn->affected_rows;
			} else {
				return false;
			}
		}

		public function uidata($table, $data, $where = null) {
			if($this->conn===null) $this->open();
			if($this->conn!==null) {
				if(empty($data))
					return true;
				if(empty($where)) {
					foreach($data as $key=>&$val)
						$this->quote($val);
//						$val = $val===null ? "null" : "'".$this->escape($val)."'";
					$keys   = array_map([$this, 'quote_field'], array_keys($data));
					$values = array_values($data);
					$query  = 'INSERT INTO '.$table.' (';
					$query .= implode(chr(44), $keys);
					$query .= ') VALUES (';
					$query .= implode(chr(44), $values);
					$query .= ') ON DUPLICATE KEY UPDATE ';
					foreach($data as $key=>&$val)
						$query .= $key.'='.$val.chr(44);
					$query  = trim($query, chr(44));
					$query .= ';';
				} else {
					$whr = [];
					foreach($data as $key=>&$val)
						$this->quote($val);
//						$val = $val===null ? "null" : "'".$this->escape($val)."'";
					foreach($where as $key=>&$val)
						$this->quote($val);
//						$val = $val===null ? "null" : "'".$this->escape($val)."'";
					$query  = 'UPDATE '.$table;
					$query .= ' SET ';
					foreach($data as $key=>&$val)
						$query .= $this->quote_field($key).'='.$val.chr(44);
					$query  = trim($query, chr(44));
					$query .= ' WHERE ';
					foreach($where as $key=>&$val)
						$whr[] = $this->quote_field($key).'='.$val;
					$query .= implode(' AND ', $whr);
					$query .= ';';
				}
				if(!$this->query($query)) {
					$this->log_error("[AlsDB:uidata] Query «".$query."» error «".$this->conn->error."»");
					return false;
				}
				return $this->conn->affected_rows;
			} else {
				return false;
			}
		}

		/* TODO array de datos a eliminar en cascada */
		public function delete($table, $where) {
			if($this->conn===null) $this->open();
			if($this->conn!==null) {
				$whr = [];
				foreach($where as $key=>&$val) {
					$this->quote($val, true);
				}
				$query  = 'DELETE FROM '.$table;
				$query .= ' WHERE ';
				foreach($where as $key=>&$val) {
					if(is_array($val)) {
						$val = implode(chr(44), $val);
						$whr[] = $key.' IN ('.$val.')';
					} else {
						$whr[] = $key.'='.$val;
					}
					unset($val);
				}
				$query .= implode(' AND ', $whr);
				$query .= ';';
				$this->log_sql($query);
				if(!$this->query($query)) {
					$this->log_error("[AlsDB:delete] Query «".$query."» error «".$this->conn->error."»");
					return false;
				}
				return true; // $this->conn->affected_rows;
			} else {
				return false;
			}
		}

		public function escape($text) {
			if($this->conn===null) $this->open();
			if($this->conn!==null) {
				return $this->conn->real_escape_string($text);
			}
			return null;
		}

		public function tr_start() {
			if($this->conn!==null) {
				$this->query("START TRANSACTION");
				return true;
			}
			return false;
		}

		public function tr_commit() {
			if($this->conn!==null) {
				$this->query("COMMIT");
				return true;
			}
			return false;
		}

		public function tr_rollback() {
			if($this->conn!==null) {
				$this->query("ROLLBACK");
				return true;
			}
			return false;
		}

		private function consume() {
			while($this->conn->more_results()) {
				$this->conn->next_result();
			}
		}

		public function quote_field($field) {
			return '`'.$field.'`';
		}

		public function quote_value($value) {
			if(!empty($value)) if(is_string($value) || is_numeric($value)) {
				return $value===null ? "null" : "'".$this->escape($value)."'";
			}
			return null;
		}

		public function quote(&$param, $recursive = false) {
			if(is_array($param)) {
				if($recursive) {
					foreach($param as &$val) {
						$this->quote($val);
						unset($val);
					}
				}
			} else {
				$param = $param===null ? "null" : "'".$this->escape($param)."'";
			}
		}

		public function extract_ids($items, $key) {
			$ids = [];
			if(is_array($items)) foreach($items as $item) {
				if(is_array($item))  $ids[] = $item[$key];
				if(is_object($item)) $ids[] = $item->$key;
			}
			return array_unique(array_filter($ids));
		}

		public function stringify_ids($ids) {
			return implode(chr(44), array_filter(array_map([$this, 'quote_value'], $ids)));
		}

		public function date($f, $ts) {
			if(empty($ts)) return null;
			return date($f, $ts);
		}

		static public function microtime() {
			$mt = microtime();
			$mt = explode(' ', $mt);
			$mt = intval(doubleval($mt[0])*1000000);
			return $mt;
		}

	}

	class Stmt {

		private $db;
		private $stmt;
		private $params;

		public function __construct($db, $conn, $query) {
			$this->db   = $db;
			$this->conn = $conn;
			$this->stmt = $this->conn->prepare($query);
			$this->params = [];
		}

		public function bind_param($value) {
			$this->params[] = &$value;
		}

		public function execute() {
			$tipes  = '';
			$values = [];
			foreach($this->params as $param) switch(true) {
				case is_int($param):		$tipes .= 'i'; $values[] = &$param; break;
				case is_float($param):  $tipes .= 'd'; $values[] = &$param; break;
				case is_string($param): $tipes .= 's'; $values[] = &$param; break;
				case is_string($param): $tipes .= 'b'; $values[] = &$param; break;
				default:						$tipes .= 's'; $values[] = &$param;
			}
			array_unshift($values, $tipes);
			$ref    = new \ReflectionClass('mysqli_stmt');
			$method = $ref->getMethod("bind_param");
			$method->invokeArgs($this->stmt, $values);
			return $this->stmt->execute();
		}

		public function bind_result(&$var) {
			$this->stmt->bind_result($var);
		}

		public function fetch() {
			$this->stmt->fetch();
		}

		public function close() {
			$this->stmt->close();
		}

	}
