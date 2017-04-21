<?php

/**
 * Class Config
 */
class Config
{
    /**
     * @var
     */
    static private $CONF;
    /**
     * @var array
     */
    static private $DEFAULT_CONF = array(
        'allow_100cpu' => '1',
        'default_language' => 'en',
        'default_codepage' => 'UTF-8',
        'default_timezone' => null,
        'self_update' => '0',
        'mirror' => 'update.eset.com',
        'update_version3' => '1',
        'update_version4' => '1',
        'update_version5' => '1',
        'update_version6' => '1',
        'update_version7' => '1',
        'update_version8' => '1',
        'update_version9' => '1',
        'update_version10' => '1',
        'update_version_x32' => '1',
        'update_version_x64' => '1',
        'update_version_ess' => '1',
        'update_version_lang' => 'bgr,chs,cht,csy,dan,deu,enu,esl,esn,eti,fin,fra,frc,hrv,hun,ita,kor,lth,nld,nor,plk,ptb,rom,rus,sky,slv,sve,tha,trk,ukr',
        'update_version_filter' => 'rel-sta',
        'find_auto_enable' => '1',
        'find_system' => null,
        'count_find_keys' => '1',
        'remove_invalid_keys' => '1',
        'default_search_query' => 'nod32+username+password',
        'default_pageindex' => '1',
        'default_page_qty' => '5',
        'default_pattern' => "((EAV|TRIAL)-[0-9]{10}).+?([a-z0-9]{10})",
        'default_recursion_level' => '2',
        'default_errors_quantity' => '5',
        'phpmailer_enable' => '0',
        'phpmailer_codepage' => 'utf-8',
        'phpmailer_smtp' => '0',
        'phpmailer_smtp_host' => null,
        'phpmailer_smtp_port' => '25',
        'phpmailer_smtp_auth' => '0',
        'phpmailer_smtp_login' => null,
        'phpmailer_smtp_password' => null,
        'phpmailer_subject' => null,
        'phpmailer_sender' => null,
        'phpmailer_recipient' => null,
        'phpmailer_level' => '1',
        'phpmailer_days' => '3',
        'log_type' => '1',
        'log_level' => '4',
        'log_dir' => 'log',
        'log_rotate_enable' => '1',
        'log_rotate_size' => '100K',
        'log_rotate_qty' => '5',
        'generate_html' => '0',
        'filename_html' => 'index.html',
        'generate_only_table' => '0',
        'html_codepage' => 'UTF-8',
        'show_login_password' => '0',
        'debug_html' => '0'
    );

    /**
     * @var array
     */
    static private $LCID = array(
        'bgr' => 1026,
        'chs' => 2052,
        'cht' => 1028,
        'csy' => 1029,
        'dan' => 1030,
        'deu' => 1031,
        'enu' => 1033,
        'esl' => 13322,
        'esn' => 3082,
        'eti' => 1061,
        'fin' => 1035,
        'fra' => 1036,
        'frc' => 3084,
        'hrv' => 1050,
        'hun' => 1038,
        'ita' => 1040,
        'kor' => 1042,
        'lth' => 1063,
        'nld' => 1043,
        'nor' => 1044,
        'plk' => 1045,
        'ptb' => 1046,
        'rom' => 1048,
        'rus' => 1049,
        'sky' => 1051,
        'slv' => 1060,
        'sve' => 1053,
        'tha' => 1054,
        'trk' => 1055,
        'ukr' => 1058
    );

    /**
     * @param $filename
     * @return null|string
     */
    static public function init($filename)
    {
        if (!file_exists($filename))
            return "Config file does not exist!";

        if (!is_readable($filename))
            return "Can't read config file! Check the file and its permissions!";

        $file = file_get_contents($filename);

        if (preg_match_all("/(.+)=(.+)/", $file, $result, PREG_PATTERN_ORDER)) {
            for ($i = 0; $i < count($result[1]); $i++) {
                if (strpos($result[1][$i], "#") !== false)
                    continue;

                if (strpos($result[2][$i], "#") !== false)
                    $result[2][$i] = substr($result[2][$i], 0, strpos($result[2][$i], "#"));

                if (trim($result[1][$i]) && (trim($result[2][$i]) || trim($result[2][$i]) == '0'))
                    self::$CONF[trim($result[1][$i])] = trim($result[2][$i]);
            }
        }
        foreach (self::$DEFAULT_CONF as $key => $value) {
            if (!isset(self::$CONF[$key]) || (empty(self::$CONF[$key]) && self::$CONF[$key] != '0'))
                self::$CONF[$key] = $value;
        }

        // Parse mirrors
        if (empty(self::$CONF['mirror']))
            self::$CONF['mirror'] = 'update.eset.com';

        self::$CONF['mirror'] = array_map("trim", (explode(",", self::$CONF['mirror'])));

        // Convert string languages in array LCID
        $lang = explode(",", strtoupper(self::$CONF['update_version_lang']));
        sort($lang);
        self::$CONF['present_languages'] = implode(", ", array_map("trim", ($lang)));
        $languages = array();

        foreach (array_map("trim", (explode(",", strtolower(self::$CONF['update_version_lang'])))) as $key) {
            $languages[] = self::$LCID[$key];
        }

        self::$CONF['update_version_lang'] = $languages;

        // Convert update_version_filter string to pcre
        self::$CONF['update_version_filter'] = preg_quote(implode('|', array_map("trim", (explode(",", self::$CONF['update_version_filter'])))));
        return null;
    }

    /**
     * @param $nm
     * @return mixed|null
     */
    static function get($nm)
    {
        return isset(self::$CONF[$nm]) ? self::$CONF[$nm] : null;
    }

    /**
     * @param $parameter
     * @return mixed|null
     */
    static public function get_default_config_parameter($parameter)
    {
        return isset(self::$DEFAULT_CONF[$parameter]) ? self::$DEFAULT_CONF[$parameter] : null;
    }

    /**
     * @param $i
     * @return int
     */
    static public function upd_version_is_set($i)
    {
        return (isset(self::$CONF['update_version' . strval($i)]) ? self::$CONF['update_version' . strval($i)] : 0);
    }

    /**
     * @return string|null
     */
    static public function check_config()
    {
        if (array_search(PHP_OS, array("Darwin", "Linux", "FreeBSD", "OpenBSD", "WINNT")) === false)
            return "This script doesn't support your OS. Please, contact developer!";

        if (function_exists("date_default_timezone_set") and function_exists("date_default_timezone_get")) {
            if (empty(self::$CONF['default_timezone'])) {
                date_default_timezone_set(@date_default_timezone_get());
            } else {
                if (@date_default_timezone_set(self::$CONF['default_timezone']) === false) {
                    self::$CONF['log_rotate_enable'] = 0;
                    return "Error in timezone settings! Please, check your config file!";
                }
            }
        }

        if (self::$CONF['log_rotate_enable'] == 1) {
            if (preg_match_all("/([0-9]+)([BKMG])/i", self::$CONF['log_rotate_size'], $result, PREG_PATTERN_ORDER)) {
                self::$CONF['log_rotate_size'] = intval(trim($result[1][0]));

                if (count($result) != 3 || self::$CONF['log_rotate_size'] < 1 || empty($result[1][0]) || empty($result[2][0]))
                    return "Please, check set up of log_rotate_size in your config file!";

                switch (trim($result[2][0])) {
                    case "G":
                        self::$CONF['log_rotate_size'] = self::$CONF['log_rotate_size'] * 1024 * 1024 * 1024;
                        break;
                    case "M":
                        self::$CONF['log_rotate_size'] = self::$CONF['log_rotate_size'] * 1024 * 1024;
                        break;
                    case "K":
                        self::$CONF['log_rotate_size'] = self::$CONF['log_rotate_size'] * 1024;
                        break;
                }
            }

            if (intval(self::$CONF['log_rotate_qty']) < 1) {
                return "Please, check set up of log_rotate_qty in your config file!";
            } else {
                self::$CONF['log_rotate_qty'] = intval(self::$CONF['log_rotate_qty']);
            }

            if (intval(self::$CONF['log_type']) < 0 || intval(self::$CONF['log_type']) > 3)
                return "Please, check set up of log_type in your config file!";
        }

        if (empty(self::$CONF['web_dir']))
            return "Please, check set up of WWW directory in your config file!";

        while (substr(self::$CONF['web_dir'], -1) == DS)
            self::$CONF['web_dir'] = substr(self::$CONF['web_dir'], 0, -1);

        while (substr(self::$CONF['log_dir'], -1) == DS)
            self::$CONF['log_dir'] = substr(self::$CONF['log_dir'], 0, -1);

        @mkdir(PATTERN, 0755, true);
        @mkdir(self::$CONF['log_dir'], 0755, true);
        @mkdir(self::$CONF['web_dir'], 0755, true);
        @mkdir(Tools::ds(self::$CONF['web_dir'], TMP_PATH, 0755, true));

        if (self::$CONF['debug_html'] == 1)
            @mkdir(Tools::ds(self::$CONF['log_dir'], DEBUG_DIR, 0755, true));

        if (self::$CONF['phpmailer_enable'] == 1) {
            if (empty(self::$CONF['phpmailer_sender']) || strpos(self::$CONF['phpmailer_sender'], "@") === FALSE || empty(self::$CONF['phpmailer_recipient']) || strpos(self::$CONF['phpmailer_recipient'], "@") === FALSE)
                return "You didn't set up email address of sender/recipient or it is wrong.Please, check your config file.";

            if (self::$CONF['phpmailer_smtp'] == 1) {
                if (empty(self::$CONF['phpmailer_smtp_host']) || empty(self::$CONF['phpmailer_smtp_port']))
                    return "Please, check SMTP host/port for using SMTP server in your config file.Or disable SMTP server if you don't wanna use it.";

                if (self::$CONF['phpmailer_smtp_auth'] == 1) {
                    if (empty(self::$CONF['phpmailer_smtp_login']) || empty(self::$CONF['phpmailer_smtp_password']))
                        return "Please, check login/password for using SMTP authorization.";
                }
            }
        }

        if (intval(self::$CONF['default_errors_quantity']) <= 0)
            self::$CONF['default_errors_quantity'] = 1;

        if (!is_readable(PATTERN))
            return "Pattern directory is not readable. Check your permissions!";

        if (!is_writable(self::$CONF['log_dir']))
            return "Log directory is not writable. Check your permissions!";

        if (!is_writable(self::$CONF['web_dir']))
            return "Web directory is not writable. Check your permissions!";

        if (self::$CONF['self_update'] < 0 || self::$CONF['self_update'] > 2)
            return "Incorrect value of self_update parameter. Must be 0,1 or 2!";

        // Link test
        $linktestfile = Tools::ds(Config::get('log_dir'), LINKTEST);
        $test = false;
        $status = false;
        if (file_exists($linktestfile)) {
            $status = file_get_contents($linktestfile);

            if (preg_match("/link|fsutil|false/", $status))
                $test = true;
        }
        if ($test == false) {
            file_put_contents(Tools::ds(self::$CONF['web_dir'], 'linktest'), '');

            if (function_exists('link') && link(Tools::ds(self::$CONF['web_dir'], 'linktest'), Tools::ds(self::$CONF['web_dir'], 'linktest2'))) {
                $status = 'link';
            } elseif (preg_match("/^win/i", PHP_OS) && shell_exec(sprintf("fsutil hardlink create %s %s", Tools::ds(self::$CONF['web_dir'], 'linktest'), Tools::ds(self::$CONF['web_dir'], 'linktest2'))) != 0) {
                $status = 'fsutil';
            } else {
                $status = 'false';
            }

            if ($status)
                unlink(Tools::ds(self::$CONF['web_dir'], 'linktest2'));

            unlink(Tools::ds(self::$CONF['web_dir'], 'linktest'));
            @file_put_contents($linktestfile, $status);
        }
        self::$CONF['create_hard_links'] = ($status != 'false' ? $status : false);

        return null;
    }
}
