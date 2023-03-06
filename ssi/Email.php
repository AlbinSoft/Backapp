<?

	namespace EngineFwk;

	use PHPMailer\PHPMailer\PHPMailer;
//	use PHPMailer\PHPMailer\Exception;

	include(PATH_ROOT.'ssi/phpmailer/PHPMailer.php');
	include(PATH_ROOT.'ssi/phpmailer/SMTP.php');
	include(PATH_ROOT.'ssi/phpmailer/Exception.php');

	class Email {

		static public function send($params) {
			$to = $bcc = $subject = $mbody = $attached = $embedded = null;
			extract($params, EXTR_IF_EXISTS);
			$mail = new PHPMailer();
			$mail->isSMTP();
			$mail->Host       = "ssl0.ovh.net";
			$mail->Port       = 465;
			$mail->SMTPAuth   = true;
			$mail->SMTPSecure = "ssl";
			$mail->Username   = "hola@albinsoft.es";
			$mail->Password   = "AS!k314qa/World";
			$mail->setFrom('hola@albinsoft.es', 'Albin Soft');
			$mail->addAddress($to);
			$mail->isHTML(true);
			$mail->Subject = $subject;
			$mail->Body    = $mbody;
			$mail->CharSet = 'UTF-8';
			if(!empty($rto)) {
				$mail->addReplyTo($rto); // , 'Reply to name'
			}
			if(!empty($bcc)) {
				$mail->addBCC($bcc); // , 'Reply to name'
			}
			if(is_array($attached)) foreach($attached as $file) {
				$mail->addAttachment($file, basename($file));
			}
			if(is_array($embedded)) foreach($embedded as $alias=>$file) {
				$mail->addEmbeddedImage($file, $alias);
			}
		//	$mail->SMTPDebug = 4;
			try {
				$ok = $mail->send();
				if(!$ok) {
					file_put_contents(PATH_ROOT.'email.log', date('Y-m-d H:i:s').' «'.$to.'» «'.$subject.'» «'.$mail->ErrorInfo.'»'.PHP_EOL, FILE_APPEND);
				}
			} catch(Exception $ex) {
				$ok = false;
				file_put_contents(PATH_ROOT.'email.log', date('Y-m-d H:i:s').' «'.$to.'» «'.$subject.'» «'.$ex->getMessage().'»'.PHP_EOL, FILE_APPEND);
			}
			return $ok;
		}

		static public function parse($tpl, $params) {
			$tpl = file_get_contents(PATH_ROOT.'emails/'.pathinfo($tpl, PATHINFO_FILENAME).'-'.LANG.'.'.pathinfo($tpl, PATHINFO_EXTENSION));
			$css = [];
			if(!empty($params['css']) && is_array($params['css']))
				foreach($params['css'] as $file)
					self::load_css($css, $file);
			foreach($params as $key=>$val) {
				if(is_array($val)) {
					$len  = strlen('{|'.$key.'}');
					$pos1 = strpos($tpl, '{|'.$key.'}');
					$pos2 = strpos($tpl, '{'.$key.'|}', $pos1);
					if($pos1 && $pos2 && $pos2>$pos1) {
						$srx = substr($tpl, $pos1, $pos2-$pos1+$len);
						$rpl = '';
						foreach($val as $item) {
							$block = substr($tpl, $pos1+$len, $pos2-$pos1-$len);
							foreach($item as $skey=>$sval) {
								$block = str_replace('{$'.$skey.'}', $sval, $block);
							}
							$rpl  .= $block;
						}
						$tpl = str_replace($srx, $rpl, $tpl);
					}
				} else {
					$tpl = str_replace('{$'.$key.'}', $val, $tpl);
				}
			}
			foreach($css as $key=>$val) {
				$tpl = str_replace('class="'.$key.'"', 'style="'.$val.'"', $tpl);
			}
			return $tpl;
		}

		static private function load_css(&$css, $file) {
			if(file_exists(PATH_ROOT.'emails/'.$file)) {
				$text  = file_get_contents(PATH_ROOT.'emails/'.$file);
				$text  = implode(PHP_EOL, array_filter(explode(PHP_EOL, $text)));
				$rules = [];
				$pattern = '|\.(\w+)\s+{\s?(.*)\s?}|';
				$count = preg_match_all($pattern, $text, $rules);
				if(FALSE!==$count && $count!==0) {
					while(0<$count--) {
						$css[$rules[1][$count]] = trim($rules[2][$count]);
					}
				}
			}
		}

	}

?>