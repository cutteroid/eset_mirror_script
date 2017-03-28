<?php

class Log {
	static private $log = array();

    static public function destruct() {}

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

	static public function informer($str, $ver, $level = 0) {
		Log::write_log($str, $level, $ver);
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
						@unlink($fn . tools::get_archive_extension() . strval($i));
						@rename($fn . tools::get_archive_extension(). strval($i-1), $fn . tools::get_archive_extension(). strval($i));
					}
					@unlink($fn . tools::get_archive_extension() . "1");
					tools::archive_file();
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
