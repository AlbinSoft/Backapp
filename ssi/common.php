<?

	error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED);
	ini_set('display_errors', '1');

	define('URL_ROOT',       'http://'.$_SERVER['HTTP_HOST'].'/backups/');
	define('PATH_ROOT',      dirname(dirname(__FILE__)).'/');
	define('PATH_TEMP',      PATH_ROOT.'tmp/');
	define('PATH_LOGS',      PATH_ROOT); // .'logs/');
	define('URI',            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
	define('URI_QS',         parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH).'?'.parse_url($_SERVER['REQUEST_URI'],  PHP_URL_QUERY));
	define('LANG',           substr(URI, 0, 4)=='/es/' ? 'es' : 'en');
	define('IS_ALBIN',       $_SERVER['REMOTE_ADDR']=='88.19.54.26');
	define('IS_DEV',         true || $_SERVER['HTTP_HOST']=='reps.beymaamerica.com');
	define('IS_POST',        !empty($_POST));
	define('IS_AJAX',        !empty($_POST['ajax']));
	define('IS_PWA',         false);
	define('ACTION',         $_REQUEST['action'] ?? FALSE);
	define('ALERTS_TO',      'albinworld@gmail.com');
	define('BRAND_NAME',     'Albin Backups');
	define('BRAND_TITLE',    '| Albin Backups');
	define('BRAND_LOGO_SVG', 'images/logo-albinsoft.svg');
	define('BRAND_LOGO_BMP', 'images/logo-albinsoft.png');

	$gerror = null;
	$config = json_decode(file_get_contents(PATH_ROOT.'config.json'));

	define('PAGE_SIZE',     (int) $config->page_size ?? 25);

	if(LANG=='en') {
		define('URL_LANG', URL_ROOT);
	} else {
		define('URL_LANG', URL_ROOT.substr(URI, 1, 3));
	}

	setLocale(LC_ALL, LANG=='en' ? 'en_GB' : 'es_ES');
	date_default_timezone_set('Europe/Viena');

	set_error_handler(function ($errno, $errstr, $errfile, $errline, $errcontext) use ($gerror) {
		$gerror = true;
		return false;
	});

	spl_autoload_register(function ($cname) {
	//	$nscn = explode('\\', $cname);
	//	$ns   = count($nscn)===2 ? $nscn[0] : null;
	//	$cn   = count($nscn)===2 ? $nscn[1] : $nscn[0];
	//	if($ns===null || $ns=='EngineFwk') {
	//		$cn = ucfirst($cn);
	//		$fp = PATH_ROOT.'ssi/'.$cn.'.php';
	//		if(file_exists($fp)) {
	//			require_once($fp);
	//			return;
	//		}
	//	}
		switch($cname) {
			case 'User':                    include(PATH_ROOT.'ssi/User.php');          break;
			case 'Urls':                    include(PATH_ROOT.'ssi/Routing.php');       break; // TODO sacarlo de la raiz (class-routing)
			case 'Paths':                   include(PATH_ROOT.'ssi/Routing.php');       break; // TODO sacarlo de la raiz (class-routing)
			case 'EngineFwk\Assets':        include(PATH_ROOT.'ssi/Assets.php');        break;
			case 'EngineFwk\Breadcrumb':    include(PATH_ROOT.'ssi/Breadcrumb.php');    break;
			case 'EngineFwk\DataStore':     include(PATH_ROOT.'ssi/DataStore.php');     break;
			case 'EngineFwk\DB':            include(PATH_ROOT.'ssi/DB.php');            break;
			case 'EngineFwk\Email':         include(PATH_ROOT.'ssi/Email.php');
			                                include(PATH_ROOT.'ssi/EmailHooks.php');    break;
			case 'EngineFwk\FileUpload':    include(PATH_ROOT.'ssi/FileUpload.php');    break;
			case 'EngineFwk\FileUploader':  include(PATH_ROOT.'ssi/FileUpload.php');    break;
			case 'EngineFwk\FileInfo':      include(PATH_ROOT.'ssi/FileUpload.php');    break;
			case 'EngineFwk\hooks':         include(PATH_ROOT.'ssi/Hooks.php');         break;
			case 'EngineFwk\AppHooks':      include(PATH_ROOT.'ssi/Hooks.php');         break;
			case 'EngineFwk\HTTP':          include(PATH_ROOT.'ssi/HTTP.php');          break;
			case 'EngineFwk\Images':        include(PATH_ROOT.'ssi/Images.php');        break;
			case 'EngineFwk\Logger':        include(PATH_ROOT.'ssi/Logger.php');        break;
			case 'EngineFwk\Notifications': include(PATH_ROOT.'ssi/Notifications.php'); break;
			case 'EngineFwk\Nonce':         include(PATH_ROOT.'ssi/Nonce.php');         break;
			case 'EngineFwk\Prozex':        include(PATH_ROOT.'ssi/Prozex.php');        break;
		//	case '':                        include(PATH_ROOT.'.php');                  break;
		}
	});

	session_id() or session_start();

	if(!IS_AJAX) {
		$assets = \EngineFwk\Assets::getInstance();
		$assets->add_path(10, PATH_ROOT);
		$assets->init();
	}

//	$user = checkuser();
//	$user && define('ULANG', $user->lang);

	// ---------- \\

	function url($path) {
		$lang = (LANG=='es' ? 'es/' : '');
		if(strpos($path, $$lang)===0)
			$path = substr($path, strlen($lang));
		if(strpos($path, $$lang)===1)
			$path = substr($path, strlen($lang)+1);
		$url  = URL_ROOT.$lang.$path;
		$url  = preg_replace('/[?&]pwa=\w+/', '', $url);
		if(!empty($qs)) {
			if(strpos($url, '?')===FALSE) {
				$url .= '?'.$qs;
			} else {

				$url .= '&'.$qs;
			}
		}
		return $url;
	}

	function sign($text) {
		return md5('busa'.$text.'reps');
	}

	function checkuser() {
		$public = [
			'/login.php',    '/logout.php',    '/user-passwd-req.php',    '/user-passwd-set.php',    '/politica-privacidad.php', '/politica-cookies.php',  '/invoices-pdf.php', '/cron-stock-load.php', '/cron-prods-images-load.php', '/webhook-import.php',
			'/en/login.php', '/en/logout.php', '/en/user-passwd-req.php', '/en/user-passwd-set.php', '/en/policy-privacy.php',   '/en/policy-cookies.php', '/en/invoices-pdf.php',
		];
		if(in_array(URI, $public)) {
			$user = $_SESSION['user'];
			if(!empty($user)) {
				$cookie = $_COOKIE['user'];
				$check  = sign($user->get_id());
				return $cookie===$check ? $user : null;
			}
			return null;
		} else {
			$url = urlencode($_SERVER['REQUEST_URI']);
			r301(empty($_SESSION['user']), Urls::login('?url='.$url.'#1'));
			$user   = $_SESSION['user'];
			$cookie = $_COOKIE['user'];
			$check  = sign($user->get_id());
			r301($cookie!==$check, Urls::login('?url='.$url.'#2#'.$cookie.'#'.$check));
			return $user;
		}
	}

	function usercan($action) {
		global $user;
		return $user->can($action);
	}

	function usercannot($action) {
		global $user;
		return !$user->can($action);
	}

	function get_query_int($key, $def = null) {
		if(isset($_GET[$key])) {
			return intval($_GET[$key]);
		}
		return $def;
	}

	function get_query_str($key) {
		if(isset($_GET[$key])) {
			$db = \EngineFwk\DB::getInstance();
			return $db->escape($_GET[$key]);
		}
		return null;
	}

	function get_post_int($key) {
		if(isset($_POST[$key])) {
			return intval($_POST[$key]);
		}
		return null;
	}

	function get_post_str($key) {
		if(isset($_POST[$key])) {
			$db = \EngineFwk\DB::getInstance();
			return $db->escape($_POST[$key]);
		}
		return null;
	}

	function get_list_raw($key, $def = null, $lname = null) {
		$skey = ($lname ?? URI).$key;
		if(IS_POST && isset($_POST[$key]) && empty($_POST[$key])) {
			set_list($key, null, $lname);
		}
		$val = $_POST[$key] ?? $_GET[$key] ?? $def; // $_SESSION[$skey] ??
		if($val!==null) {
			$val = trim($val);
			set_list($key, $val, $lname);
			return $val;
		}
		return null;
	}

	function get_list_int($key, $def = null, $lname = null) {
		$val = get_list_raw($key, $def, $lname);
		if(!str_empty($val)) {
			return intval($val);
		}
		return null;
	}

	function get_list_str($key, $def = null, $lname = null) {
		$val = get_list_raw($key, $def, $lname);
		if($val!==null) {
			$db = \EngineFwk\DB::getInstance();
			return $db->escape($val);
		}
		return null;
	}

	function set_list($key, $val, $lname) {
		$skey = ($lname ?? URI).$key;
//	var_dump($key, $val, $lname, $skey);
		$_SESSION[$skey] = $val;
	}

	function str_empty($val) {
		return $val===null || $val==='';
	}

	function pagination($cfg = []) {
		$link = $page = $pages = $follow = $wrap_class = $prevnext = $prev_alt = $next_alt = $dataset = $payload = null;
		$cfg = array_merge([
			'wrap_class' => 'pager',
			'prevnext'   => 'entity',	// false|entity|span
			'prev_alt'   => false,		// false|string
			'next_alt'   => false,		// false|string
			'dataset'    => true,
			'payload'    => null,
		], $cfg);
		extract($cfg, EXTR_IF_EXISTS); // var_dump($link, $page, $pages, $follow, $wrap_class, $prevnext, $prev_alt, $next_alt, $dataset);

		$page = max(1, $page);

		if($link!==FALSE) {
			$url = parse_url($link);
			$url_qstr = [];
			$url_base = "{$url['scheme']}://{$url['host']}{$url['path']}";			// parse_url($link, PHP_URL_SCHEME | PHP_URL_HOST | PHP_URL_PATH);
			$url_base = preg_replace('|page/\d+/|', '', $url_base);
			parse_str($url['query'] ?? '', $url_qstr);									// parse_url($link, PHP_URL_QUERY);
			unset($url_qstr['page']);
		} else {
			$url_base = '#';
			$url_qstr = [];
		}

		$pagelink = function ($page) use ($url_base, $url_qstr, $link) {
			if($link!==FALSE) {
				return $url_base.($page==1 ? '' : 'page/'.$page.'/').(empty($url_qstr) ? '' : '?'.http_build_query($url_qstr));
			}
			return $url_base;
		};

//	var_dump($link, $url, $url_base, $url_qstr);
//	var_dump($url_base, $url_qstr);

		$rel   = $follow ? ''           : 'rel="nofollow"';
		$reln  = $follow ? 'rel="next"' : 'rel="nofollow"';
		$relp  = $follow ? 'rel="prev"' : 'rel="nofollow"';

		$first = 1;
		$pf_ini = false; $pf_fin = 0;									// primer framento
		$uf_fin = false; $uf_ini = 0;									// último framento
		$fc_ini = max(1, $page-2);										// framento central
		$fc_fin = min($pages, $page+2);								// framento central
//		if($first==$page && $fc_fin+1<$pages) $fc_fin++;		// framento central, se amplía si estás en los extremos
//		if($page==$pages && $fc_ini-1>$first) $fc_ini--;		// framento central, se amplía si estás en los extremos

		if($fc_ini > $first) {											// si el fragmento central no abarca la primera página, hace falta un primer fragmento
			$pf_ini = $first;
			$pf_fin = min($fc_ini-1, $pf_ini+2);					// o tiene dos páginas o llega hasta el número anterior al primero del fragmento central
		}
		if($fc_fin < $pages) {											// si el fragmento central no abarca la última página, hace falta un último fragmento
			$uf_fin = $pages;
			$uf_ini = max($fc_fin+1, $uf_fin-2);					// o tiene dos páginas o empieza en el número siguiente al último del fragmento central
		}
		$pf_dotsa = $fc_ini-$pf_fin > 1;		// always			// si hay hueco entre el último del primer fragmento y el primero del fragmento central
		$pf_dotsr = $fc_ini-$pf_ini > 1;		// responsive		// si hay hueco entre el primero del primer fragmento y el primero del fragmento central
		$uf_dotsa = $uf_ini-$fc_fin > 1;		// always			// si hay hueco entre el último del fragmento central y el primero del último fragmento
		$uf_dotsr = $uf_fin-$fc_fin > 1;		// responsive		// si hay hueco entre el último del fragmento central y el último del último fragmento

		$payload  = $dataset && $payload ? ' data-payload="'.esc_attr($payload).'"' : '';

		echo '<nav class="'.$wrap_class.'">';
		if($prevnext!==FALSE) {
			$prev_ico = $prevnext=='entity' ? '&lt;' : '<span class="ico_prev"></span>';
			$prev_alt = $prev_alt!==FALSE ? ' '.tt($prev_alt, 'pagination') : '';
			$datapage = $dataset ? ' data-page="'.($page-1).'"' : '';
			if($page!=1) {
				echo '<a class="prev" '.$rel.' href="'.$pagelink($page-1).'"'.$datapage.$payload.'>'.$prev_ico.$prev_alt.'</a>';
			} else {
				echo '<span class="prev">'.$prev_ico.$prev_alt.'</span>';
			}
		}

		if($pf_ini) for($itr=$pf_ini; $itr<=$pf_fin; $itr++) {
			$datapage = $dataset ? ' data-page="'.$itr.'"' : '';
			$cls = 'goto'.($itr!==$pf_ini && ($pf_dotsa || $pf_dotsr) ? ' nomob' : ''); // los no vitales se pueden esconder si se pusieron dots en su lugar
			if($page!==$itr) {
				echo '<a class="'.$cls.'" '.$rel.' href="'.$pagelink($itr).'"'.$datapage.$payload.'>'.$itr.'</a>';
			} else {
				echo '<span class="'.$cls.'">'.$itr.'</span>';
			}
		}

		if($pf_dotsa) { // $fc_ini-$pf_fin>1
			echo '<span class="dots">&hellip;</span>';
		} elseif($pf_dotsr) {
			echo '<span class="dots nodsk">&hellip;</span>';
		}

//		echo '<span class="dots">&mdash;</span>';

		for($itr=$fc_ini; $itr<=$fc_fin; $itr++) {
			$datapage = $dataset ? ' data-page="'.$itr.'"' : '';
			if($page!==$itr) {
				echo '<a class="goto" '.$rel.' href="'.$pagelink($itr).'"'.$datapage.$payload.'>'.$itr.'</a>';
			} else {
				echo '<span class="goto">'.$itr.'</span>';
			}
		}

//		echo '<span class="dots">&mdash;</span>';

		if($uf_dotsa) { // $uf_ini-$fc_fin>1
			echo '<span class="dots">&hellip;</span>';
		} elseif($uf_dotsr) {
			echo '<span class="dots nodsk">&hellip;</span>';
		}

		if($uf_ini) for($itr=$uf_ini; $itr<=$uf_fin; $itr++) {
			$datapage = $dataset ? ' data-page="'.$itr.'"' : '';
			$cls = 'goto'.($itr!==$uf_fin && ($uf_dotsa || $uf_dotsr) ? ' nomob' : ''); // los no vitales se pueden esconder si se pusieron dots en su lugar
			if($page!==$itr) {
				echo '<a class="'.$cls.'" '.$rel.' href="'.$pagelink($itr).'"'.$datapage.$payload.'>'.$itr.'</a>';
			} else {
				echo '<span class="'.$cls.'">'.$itr.'</span>';
			}
		}

		if($prevnext!==FALSE) {
			$next_ico = $prevnext=='entity' ? '&gt;' : '<span class="ico_next"></span>';
			$next_alt = $next_alt!==FALSE ? tt($next_alt, 'pagination').' ' : '';
			$datapage = $dataset ? ' data-page="'.($page+1).'"' : '';
			if($page!=$pages) {
				echo '<a class="next" '.$rel.' href="'.$pagelink($page+1).'"'.$datapage.$payload.'>'.$next_alt.$next_ico.'</a>';
			} else {
				echo '<span class="next">'.$next_alt.$next_ico.'</span>';
			}
		}
		echo '</nav>';
	}

	function get_post($info, $fback) {
		$rv = [];
		$db = \EngineFwk\DB::getInstance();
		foreach($info as $item) {
			$item = (object) $item;
			$temp = $_POST[$item->field] ?? null;
			switch($item->validate) {
				case 'empty':
					if(empty($temp) && strlen($temp)===0) $fback->error($item->alert);
				break;
			}
			switch($item->cast) {
				case 'date':            $temp = strtodate($temp);                   break;
				case 'time':            $temp = timetointeger($temp);               break;
				case 'datetime':        $temp = strtodatetime($temp);               break;
				case 'boolean':         $temp = boolval($temp);                     break;
				case 'integer':         $temp = intval($temp);                      break;
				case 'float':           $temp = doubleval($temp);                   break;
				case 'string':          $temp = stripslashes($temp);                break;
				case 'array.integer':   $temp = array_map('intval', $temp);         break;
				case 'nif':             $temp = normalize_nif($temp);               break;
				case 'point':           $temp = normalize_point($temp);             break;
			}
			$rv[$item->field] = $temp;
		}
		return $rv;
	}

	// http://sandbox.onlinephpfunctions.com/code/dba70e8a33095a77e99b375e2da3989e57a28945

	function strtodate($dmy) {
		$mtxs = [];
		if(1===preg_match('|(\d{1,2})\/(\d{1,2})\/(\d{1,4})|', $dmy, $mtxs)) {
			return str_pad($mtxs[3], 4, '20', STR_PAD_LEFT).'-'.str_pad($mtxs[2], 2, '0', STR_PAD_LEFT).'-'.str_pad($mtxs[1], 2, '0', STR_PAD_LEFT);
		}
		if(1===preg_match('|(\d{4})-(\d{2})-(\d{2})|', $dmy, $mtxs)) {
			return str_pad($mtxs[1], 4, '20', STR_PAD_LEFT).'-'.str_pad($mtxs[2], 2, '0', STR_PAD_LEFT).'-'.str_pad($mtxs[3], 2, '0', STR_PAD_LEFT);
		}
		return null;
	}

	function normalize_nif($nif) {
		$nif = preg_replace('/([a-zA-Z]*)[ -]?(\d+)[ -]?([a-zA-Z]*)/', '$2$1$3', $nif);
		return strtoupper($nif);
	}

	function normalize_point($coords) {
		$coords = str_replace(chr(44), chr(46), $coords);
		$coords = explode(' ', $coords);
		$coords = array_pad($coords, 2, '0');
		$coords = array_slice($coords, 0, 2);
		$coords = array_map('number_format', array_map('doubleval', $coords), [12, 12]);
		$coords = implode(chr(44), $coords);
		$coords = "POINT($coords)";
		return new \EngineFwk\DBLit($coords);
	}

	function flt_select($name, $rows, $key, $lbl, $sel, $el = null, $ev = null) {
		?><select id="<?=$name ?>"name="<?=$name ?>"><?
			if($ev!==null) {
				?><option value="<?=$ev ?>"><?=$el ?></option><?
			}
			foreach($rows as $row) {
				?><option value="<?=$row->$key ?>" <?=($row->$key==$sel ? 'selected' : '') ?>><?=$row->$lbl ?></option><?
			}
		?></select><?
	}

	function flt_cboxes($name, $rows, $key, $lbl, $sel) {
		?><span class="cboxes"><?
			foreach($rows as $k=>$row) {
				?><span class=""><input type="checkbox" id="<?=$name ?><?=$k ?>" name="<?=$name ?>[]" value="<?=$row->$key ?>" <?=(in_array($row->$key, $sel) ? 'checked' : '') ?> /><label for="<?=$name ?><?=$k ?>"><?=$row->$lbl ?></label></span><?
			}
		?></span><?
	}

	function r301($condition, $redirection) {
		if($condition) {
			if(!str_starts_with($redirection, URL_ROOT)) {
				$redirection = url($redirection);
			}
			header('Location: '.$redirection);
			die();
		}
	}

	function array_flatten($a) {
		$r = [];
		array_walk($a, function ($a) use (&$r) { foreach($a as $b) $r[] = $b; } );
		return $r;
	}

	function datedef($f, $dt, $d) {
		if(empty($dt)) return $d;
		if(is_string($dt)) {
			$dt = strtotime($dt);
		}
		return date($f, $dt);
	}

	function nprintf($n, $d = 0) {
		return number_format($n, $d, lit('common.number.decimal_sep'), lit('common.number.thousand_sep'));
	}

	function cast($var, $class) {
		if(is_array($var)) {
			return new $class((object) $var);
		}
		if(is_object($var)) {
			if(get_class($var)!==$class) {
				return new $class($newfile);
			} else {
				return $var;
			}
		}
		return null;
	}

	function recstocsv($rows, $fpath, $frel = null) {
		$output = fopen($fpath, 'w'); // 'php://output'
		fwrite($output, pack("CCC", 0xef, 0xbb, 0xbf));
		$headers = array_keys((array) $rows[0]);
		if(!empty($frel)) {
			$csv = [];
				foreach($frel as $temp=>$header) {
				$csv[] = $header;
				unset($header);
			}
			fputcsv($output, $csv);
		} else {
			fputcsv($output, $headers);
		}
		foreach($rows as $row) {
			if(!empty($frel)) {
				$csv = [];
				foreach($frel as $header=>$temp) {
					$csv[] = $row->$header;
				}
				fputcsv($output, $csv);
			} else {
				fputcsv($output, (array) $row);
			}
		}
		fclose($output);
	}

	function download($fpath, $type) {
		header('Content-Type: text/plain');
		$mtype = mime_content_type($fpath);
		header('Content-Type: '.$mtype); // application/octet-stream
		header('Content-Transfer-Encoding: binary');
		header('Content-Disposition: attachment; filename='.basename($fpath));
		header('Pragma: no-cache');
	//	header('Expires: 0');
	//	header('Cache-Control: must-revalidate');
		readfile($fpath);
		unlink($fpath);
		die();
	}

		function str_starts_with($haystack, $needle) {
			return strpos($haystack, $needle)===0;
		}
	if(!function_exists('str_starts_with')) {
	}

	function esc_attr($text) {
		return htmlspecialchars($text, ENT_QUOTES);
	}

	function prump(...$args) {
		echo '<pre>'; var_dump(...$args); echo '</pre>';
	}