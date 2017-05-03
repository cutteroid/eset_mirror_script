<?php

ini_set('memory_limit', '16M');
chdir(substr(dirname(__FILE__), 0, -3));

$DIRECTORIES = array(
    3 => 'eset_upd',
    4 => 'eset_upd/v4',
    5 => 'eset_upd/v5',
    6 => 'eset_upd/v6',
    7 => 'eset_upd/v7',
    8 => 'eset_upd/v8',
    9 => 'eset_upd/v9',
    10 => 'eset_upd/v10',
);

$SELFUPDATE_POSTFIX = array(
    "changelog.rus",
    "changelog.eng",
);

define('DS', DIRECTORY_SEPARATOR);
define('VERSION', '1.0.20170502 [Freedom for Ukraine][Moded by harmless]');
define('SELF', substr(dirname(__FILE__), 0, -3));
define('INC', SELF . "inc" . DS);
define('CLASSES', SELF . "inc" . DS . "classes" . DS);
define('PATTERN', SELF . "pattern" . DS);
define('TOOLS', SELF . "tools" . DS);
define('CONF_FILE', SELF . "nod32ms.conf");
define('LANGPACKS_DIR', 'langpacks');
define('DEBUG_DIR', 'debug');
define('KEY_FILE_VALID', 'nod_keys.valid');
define('KEY_FILE_INVALID', 'nod_keys.invalid');
define('LOG_FILE', 'nod32ms.log');
define('SUCCESSFUL_TIMESTAMP', 'nod_lastupdate');
define('LINKTEST', 'nod_linktest');
define('DATABASES_SIZE', 'nod_databases_size');
define('TMP_PATH', 'tmp');
define('SELFUPDATE_SERVER', "eset.contra.net.ua");
define('SELFUPDATE_PORT', "2221");
define('SELFUPDATE_DIR', "nod32ms");
define('SELFUPDATE_FILE', "files.md5");
define('SELFUPDATE_NEW_VERSION', "version.txt");
define("CONNECTTIMEOUT", 5); # Seconds

function __autoload($class)
{
    @include_once CLASSES.$class.".class.php";
}
