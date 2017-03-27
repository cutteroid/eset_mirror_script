<?php

class Tools {

 static public function download_file($source, $dest, $buffer = 1024) {
  ini_set('default_socket_timeout', CONNECTTIMEOUT);
  $header = @get_headers($source, 1);
  ini_restore('default_socket_timeout');
  if (is_array($header)){
   if (preg_match("/200/", $header[0])){
    $dir = dirname($dest);
    if (!file_exists($dir)) @mkdir($dir, 0755, true);
    $in = fopen($source, 'rb', false);
    $out = fopen($dest, "wb");
    while ($chunk = fread($in, $buffer)) fwrite($out, $chunk, $buffer);
    fclose($in);
    fclose($out);
   }
   return $header;
  } else return false;
 }
 
 static public function get_archive_extension() {
  switch(PHP_OS) {
   case "Darwin":
   case "Linux":
   case "FreeBSD":
   case "OpenBSD": return ".gz";
   case "WINNT": return ".zip";
  }
  throw  new Exception("Your OS not supported");
 }    

 static public function archive_file($source, $dest) {
     $fp = gzopen("$dest.1.gz", 'w9'); // w == write, 9 == highest compression
     gzwrite($fp, file_get_contents("$source"));
     gzclose($fp);
     unlink("$source");
 }

 static public function extract_file($source, $dest) {
  switch(PHP_OS) {
   case "Darwin": 
   case "Linux":
   case "FreeBSD":
   case "OpenBSD": system(sprintf("`which unrar` x -inul -y %s %s", $source, $dest)); break;
   case "WINNT": shell_exec(sprintf(TOOLS."unrar.exe e -y %s %s", $source, $dest)); break;
  }
 }

 static public function ping($hostname, $port = 80) {
  $fs = @fsockopen($hostname, $port, $errno, $errstr, CONNECTTIMEOUT);
  if ($fs) {
   fclose($fs);
   return true;
  } else return false;
 }
 
 static public function bytesToSize1024($bytes, $precision = 2) {
  $unit = array('Bytes', 'KBytes', 'MBytes', 'GBytes', 'TBytes', 'PBytes', 'EBytes');
  return @round(
   $bytes / pow(1024, ($i = floor(log($bytes, 1024)))), $precision
  ).' '.$unit[intval($i)];
 }
 
 static public function secondsToHumanReadable($secs) {
  if ($secs > 60*60*24) return gmdate("H:i:s", $secs);
  else return gmdate("i:s", $secs);
 }

 static public function ds() {
  $args = func_get_args();
  return preg_replace('/[\/\\\\]+/', DIRECTORY_SEPARATOR, implode('/', $args));
 }
 
 static public function conv($text, $to_encoding) {
  if (preg_match("/utf-8/i", $to_encoding)) return $text;
  elseif (function_exists('mb_convert_encoding')) return mb_convert_encoding($text, 'UTF-8', $to_encoding);
  elseif (function_exists('iconv')) return iconv('UTF-8', $to_encoding, $text);
  else {
   $conv = array();
   for($x=128;$x<=143;$x++)  {
    $conv['u'][]=chr(209).chr($x);
    $conv['w'][]=chr($x+112);
  
   }
   for($x=144;$x<=191;$x++)  {
    $conv['u'][]=chr(208).chr($x);
    $conv['w'][]=chr($x+48);
   }
   $conv['u'][]=chr(208).chr(129);
   $conv['w'][]=chr(168);
   $conv['u'][]=chr(209).chr(145);
   $conv['w'][]=chr(184);
   $conv['u'][]=chr(208).chr(135);
   $conv['w'][]=chr(175);
   $conv['u'][]=chr(209).chr(151);
   $conv['w'][]=chr(191);
   $conv['u'][]=chr(208).chr(134);
   $conv['w'][]=chr(178);
   $conv['u'][]=chr(209).chr(150);
   $conv['w'][]=chr(179);
   $conv['u'][]=chr(210).chr(144);
   $conv['w'][]=chr(165);
   $conv['u'][]=chr(210).chr(145);
   $conv['w'][]=chr(180);
   $conv['u'][]=chr(208).chr(132);
   $conv['w'][]=chr(170);
   $conv['u'][]=chr(209).chr(148);
   $conv['w'][]=chr(186);
   $conv['u'][]=chr(226).chr(132).chr(150);
   $conv['w'][]=chr(185);
   $win = str_replace($conv['u'],$conv['w'],$text);
   
   if (preg_match("/1251/i", $to_encoding)) return $win;
   elseif (preg_match("/koi8/i", $to_encoding)) return convert_cyr_string($win, 'w', 'k');
   elseif (preg_match("/866/i", $to_encoding)) return convert_cyr_string($win, 'w', 'a');
   elseif (preg_match("/mac/i", $to_encoding)) return convert_cyr_string($win, 'w', 'm');
   else return $text;
  } 
 }
 
 static public function parse_url($url) { 
  if(strpos($url,"://")===false && substr($url,0,1)!="/") $url = "http://".$url;
  $parsed_url = parse_url($url);
  $parsed_url['scheme'] = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : 'http://'; 
  $parsed_url['host']  = isset($parsed_url['host']) ? $parsed_url['host'] : ''; 
  $parsed_url['port']  = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : ''; 
  $parsed_url['user']  = isset($parsed_url['user']) ? $parsed_url['user'] : ''; 
  $parsed_url['pass']  = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : ''; 
  $parsed_url['pass']  = ($parsed_url['user'] || $parsed_url['pass']) ? $parsed_url['pass'] . "@" : ''; 
  $parsed_url['path']  = isset($parsed_url['path']) ? (preg_match('#/$#', $parsed_url['path']) ? $parsed_url['path'] : $parsed_url['path'] . '/') : '/'; 
  $parsed_url['query'] = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : ''; 
  $parsed_url['fragment'] = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : ''; 
  return $parsed_url; 
 } 
}
