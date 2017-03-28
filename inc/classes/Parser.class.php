<?php

class Parser {

 static public function parse_line($handle, $tag, $pattern = false) {
  $arr = array();
  if (preg_match_all( ($pattern ? $pattern : "/$tag *=(.+)/") , $handle, $result, PREG_PATTERN_ORDER)) {
   foreach ($result[1] as $key) {
    $arr[] = trim($key);
   }
  }
  return $arr;
 }
    
 static public function parse_keys($file) {
  return parser::parse_line(file_get_contents($file),false,"/(.+:.+)\n/");
 }

 static public function delete_parse_line_in_file($str_line, $filename) {
  $content = file($filename);
  for ($i=0; $i<count($content); $i++) {
   if (strpos($content[$i], $str_line) !== false) {
    unset($content[$i]);
   }
  }
  $content = implode("", $content);
  file_put_contents($filename, $content);
 }
    
 static public function parse_template($handle, $template, &$logins, &$passwds) {
  if (preg_match_all("/$template/s", $handle, $result, PREG_PATTERN_ORDER)) {
   for ($i=0; $i < count($result[1]); $i++) {
    if (!empty($result[1][$i]) && !empty($result[3][$i])) {
     $logins[] = $result[1][$i];
     $passwds[] = $result[3][$i];
    }
   }
  }
 }
 
 static public function parse_header($http_response_header) {
  $header = array();
  foreach ($http_response_header as $line){
   if (preg_match("/\:/", $line)){
    $parse = array_map("trim", explode(":", $line, 2));
    $header = array_merge_recursive($header, array($parse[0]=>$parse[1]));
   } else {
    $header = array_merge_recursive($header, array($line));
   }
  }
  return $header;
 }
}
