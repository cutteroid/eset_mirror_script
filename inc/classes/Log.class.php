<?php

class Log {
	static private $log = array();
	static private $icq_log = "";
	static private $mailer_log = "";

    static public function destruct() {
		if(!empty(self::$icq_log) && Config::get('icq_informer_enable') == '1') {
			self::$icq_log = Tools::conv(self::$icq_log, Config::get('icq_informer_codepage'));
			
			$icq = new WebIcqLite();
			if($icq->connect(Config::get('icq_informer_login'), Config::get('icq_informer_password'))) {
				if(!$icq->send_message(Config::get('icq_informer_destination'), self::$icq_log)) {
					Log::write_log($icq->error, 0);
				}
				$icq->disconnect();
			}
			else {
				Log::write_log($icq->error, 0);
			}
		}
		if(!empty(self::$mailer_log) && Config::get('phpmailer_enable') == '1') {
			$mailer = new PHPMailer();
			if(Config::get('phpmailer_smtp') == '1') {
				$mailer->Host = Config::get('phpmailer_smtp_host');
				$mailer->Port = Config::get('phpmailer_smtp_port');
				$mailer->CharSet = Config::get('phpmailer_codepage');
				$mailer->Mailer = "smtp";
				if(Config::get('phpmailer_smtp_auth') == '1') {
					$mailer->SMTPAuth = true;
					$mailer->SMTPSecure = Config::get('phpmailer_secure');
					$mailer->Username = Config::get('phpmailer_smtp_login');
					$mailer->Password = Config::get('phpmailer_smtp_password');
				}
				else {
					$mailer->SMTPAuth = false;
				}
			}
			$mailer->Priority = 3;
			$mailer->Subject = Tools::conv(Config::get('phpmailer_subject'), Config::get('phpmailer_codepage'));
			if(Config::get('phpmailer_level') == '3'){
				self::$mailer_log = implode("\r\n", self::$log);
			}
			$mailer->Body = Tools::conv(self::$mailer_log, Config::get('phpmailer_codepage'));
			$mailer->SetFrom(Config::get('phpmailer_sender'), "NOD32 mirror script");
			$mailer->AddAddress(Config::get('phpmailer_recipient'), "Admin");
			$mail->SMTPDebug = 1;
			if(!$mailer->Send()) {
				Log::write_log($mailer->ErrorInfo, 0);
			}
			$mailer->ClearAddresses();
			$mailer->ClearAttachments();
		}
    }

	static public function write_to_file($filename, $text, $is_log_dir = false) {
		$file_name = $is_log_dir ? $filename : Tools::ds(Config::get('log_dir'), $filename);
		$f = fopen($file_name, "a+");
		if(!feof($f)) {
			fwrite($f, $text);
		}
		fflush($f);
		fclose($f);
		clearstatcache();
	}

	static public function informer($str, $ver, $level) {
		Log::write_log($str, 0, $ver);
		if(Config::get('icq_informer_level') >= $level) {
			self::$icq_log .= sprintf("[%s] [%s] %s%s", date("Y-m-d"), date("H:i:s"), ($ver ? '[ver. ' . strval($ver) . '] ' : ''), $str) . chr(10);
		}
		if(Config::get('phpmailer_level') >= $level) {
			self::$mailer_log .= sprintf("[%s] [%s] %s%s", date("Y-m-d"), date("H:i:s"), ($ver ? '[ver. ' . strval($ver) . '] ' : ''), $str) . chr(10);
		}
	}

    static public function write_log($text, $level, $version = null, $ignore_rotate = false) {
		if(empty($text)) return null;
		if(Config::get('log_type') == '0') return null;
		if(Config::get('log_level') < $level) return null;
		$fn = Tools::ds(Config::get('log_dir'), LOG_FILE);
		if(Config::get('log_rotate_enable') == 1) {
			if(file_exists($fn) && !$ignore_rotate) {
				if(filesize($fn) >= Config::get('log_rotate_size')) {
					Log::write_log(Language::t("Log file was cutted due rotation..."), 0, null, true);
					array_pop(self::$log);
					for($i=Config::get('log_rotate_qty'); $i>1; $i--) {
						@unlink($fn . strval($i) . tools::get_archive_extension());
						@rename($fn . strval($i-1) . tools::get_archive_extension(), $fn . strval($i) . tools::get_archive_extension());
					}
					@unlink($fn . "1" . tools::get_archive_extension());
					tools::archive_file(Config::get('log_dir'), LOG_FILE, $fn . "1");
					@unlink($fn);
					Log::write_log(Language::t("Log file was cutted due rotation..."), 0, null, true);
					array_pop(self::$log);
				}
			}
		}
		if($level == 1) {
			Log::informer($text, $version, 0);
		}
		else {
			$text = sprintf("[%s] %s%s", date("Y-m-d, H:i:s"), ($version ? '[ver. ' . strval($version) . '] ' : ''), $text);
			if(Config::get('log_type') == '1' || Config::get('log_type') == '3') {
				self::write_to_file(LOG_FILE, Tools::conv($text."\r\n", Config::get('default_codepage')));
			}
			if(Config::get('log_type') == '2' || Config::get('log_type') == '3') {
				echo Tools::conv($text, Config::get('default_codepage')).chr(10);
			}		
		}
		self::$log[] = $text;
    }

}

?>