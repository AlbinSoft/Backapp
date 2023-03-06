<?

	namespace Arrakis;

	class Richsnippet {

		function __construct($type, $parent = null) {
			$this->type   = $type;
			$this->parent = $parent;
			$this->pairs  = [];
		}

		function add_value($key, $val) {
			$this->pairs[$key] = (json_encode($val)); // addslashes
		}

		function add_type($key, $type) {
			$obj = new Richsnippet($type, $this);
			$this->pairs[$key] = $obj;
			return $obj;
		}

		function get_script() {
			$json  = '';
			$pairs = [];
			if(empty($this->parent)) {
				$pairs[] = '"@context": "http://schema.org"';
			}
			$pairs[] = '"@type":"'.$this->type.'"';
			foreach($this->pairs as $key=>$val) {
				if(is_object($val)) {
					$pairs[] = '"'.$key.'":'.$val->get_script().'';
				} else {
					$pairs[] = '"'.$key.'":'.$val.'';
				}
			}
			if(empty($this->parent)) {
				$json .= '<script type="application/ld+json">';
				$json .= '{'.implode(chr(44), $pairs).'}';
				$json .= '</script>';
			} else {
				$json .= '{'.implode(chr(44), $pairs).'}';
			}
			return $json;
		}

	}
