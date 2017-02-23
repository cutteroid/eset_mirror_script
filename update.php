#!/usr/bin/env php
<?php
error_reporting(E_ERROR);
ini_set('display_errors', 0);

require_once "inc/init.php";

foreach (glob(CLASSES."*.class.php") as $filename) {
 include_once($filename);
}

if (($err = Config::init(CONF_FILE)) || ($err = Language::init(Config::get('default_language')) ) || ($err = Language::t(Config::check_config()))) {
 Log::write_log(Language::t($err), 0);
 exit;
}

if (Config::get('self_update') > 0) {
 if (Tools::ping(SELFUPDATE_SERVER, SELFUPDATE_PORT) === true) {
  SelfUpdate::init();
  if (SelfUpdate::is_need_to_update()) {
   Log::informer(Language::t("New version is available on server [%s]!",SelfUpdate::get_version_on_server()), null, 0);
   if (Config::get('self_update') > 1) {
    SelfUpdate::start_to_update();
    Log::informer(Language::t("Your script has been successfully updated to version %s!",SelfUpdate::get_version_on_server()), null, 0);
    Log::destruct();
    exit;
   }
  } else {
   Log::write_log(Language::t("You already have actual version of script! No need to update!"), 0);
  }
 } else {
  Log::write_log( Language::t("Update server is down!"), 0 );
 }
}

$nod32ms = new Nod32ms();
