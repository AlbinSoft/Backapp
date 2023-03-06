<?

	namespace EngineFwk;

	class Breadcrumb {

		static private $instance;

		static public function instance() {
			if(empty(self::$instance))
				self::$instance = new Breadcrumb();
			return self::$instance;
		}

		private $items = array();

		private function __construct() {
		}

		public function add($title, $url) {
			$item = (object) [];
			$item->title = $title;
			$item->url   = $url;
			$this->items[] = $item;
			return $this;
		}

		public function render() {
			$html  = '';
			$html .= '<div class="breadcrumb">';
			$html .= '<p class="center">';
		//	$html .= ' <a href="'.home_url('').'" title="Inicio">Inicio</a> » ';
			$last  = count($this->items)-1;
			foreach($this->items as $itr=>$item) {
				if($item->url===null) {
					$html .= '<span>'.$item->title.'</span>';
				} else {
					$html .= '<a href="'.$item->url.'" title="'.$item->title.'">';
					$html .= $item->title;
					$html .= '</a>';
				}
				if($itr!==$last) {
					$html .= ' » ';
				}
			}
			$html .= '</p>';
			$html .= '</div>';
			return $html;
		}

		public function getScript() {
			if(empty($this->items)) { // TODO && !is_front_page()
				return '<script>alert("Falta BC")</script>';
			}
			$json  = '';
			$json .= '<script type="application/ld+json">';
			$json .= '{';
			$json .= ' "@context": "http://schema.org",';
			$json .= ' "@type": "BreadcrumbList",';
			$json .= ' "itemListElement": [';
			foreach($this->items as $itr=>$item) {
				$json .= ' {';
				$json .= ' "@type": "ListItem",';
				$json .= ' "position": '.($itr+1).',';
				$json .= ' "item": {';
				$json .= ' "@id": "'.($item->url).'",';
				$json .= ' "name": "'.htmlentities($item->title).'"';
				$json .= ' }';
				$json .= ' },';
			}
			$json  = substr($json, 0, -1);
			$json .= ' ]';
			$json .= ' }';
			$json .= '</script>';
			return $json;
		}

	}

