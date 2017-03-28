<?php

class Language {
 static private $language = null;
 static private $language_file;
 static private $language_pack;
 static private $default_language_pack;
 static private $default_language_file;
 
 static public function init($lang) {
  Language::$language = $lang;
  Language::$language_file = Tools::ds(SELF, LANGPACKS_DIR, $lang.'.ini');
  Language::$default_language_file = Tools::ds(SELF, LANGPACKS_DIR, Config::get_default_config_parameter('default_language').'.ini');
  if ($lang != 'en') {
   if (!file_exists(Language::$language_file)) {
    return sprintf("Language file [%s.ini] does not exist!", $lang);
   }
  }
  else {
   return null;
  }
  
  $tmp = file(Language::$language_file);
  Language::$default_language_pack = file(Language::$default_language_file);
  if (count($tmp) != count(Language::$default_language_pack)) return sprintf("Language file [%s] is corrupted!", $lang);
  for ($i=0; $i<count($tmp); $i++) {
   Language::$language_pack[trim($tmp[$i])] = trim(Language::$default_language_pack[$i]);
  }
  return null;
 }

 static public function t() {
  $text = func_get_arg(0);
  $params = func_get_args(); array_shift($params);
  if (Language::$language == Config::get_default_config_parameter('default_language')) return vsprintf($text, $params);
  $key = array_search($text, Language::$language_pack);
  return ($key != FALSE) ? vsprintf($key, $params) : vsprintf($text, $params);
 }
}
