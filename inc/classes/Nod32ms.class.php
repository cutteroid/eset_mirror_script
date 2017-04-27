<?php

/**
 * Class Nod32ms
 */
class Nod32ms
{
    /**
     * @var
     */
    static private $start_time;

    /**
     * Nod32ms constructor.
     */
    public function __construct()
    {
        Nod32ms::$start_time = time();
        Log::write_log(Language::t("Run script %s", VERSION), 0);
        $this->run_script();
    }

    /**
     * Nod32ms destructor.
     */
    public function __destruct()
    {
        Log::write_log(Language::t("Total working time: %s", Tools::secondsToHumanReadable(time() - Nod32ms::$start_time)), 0);
        Log::destruct();
        Log::write_log(Language::t("Stop script."), 0);
    }

    /**
     * @param $ver
     * @param bool $return_time_stamp
     * @return mixed|null
     */
    private function check_time_stamp($ver, $return_time_stamp = false)
    {
        $days = Config::get('icq_informer_days') * 24 * 60 * 60;
        $fn = Tools::ds(Config::get('log_dir'), SUCCESSFUL_TIMESTAMP);
        $timestamps = array();

        if (file_exists($fn)) {
            $handle = file_get_contents($fn);
            $content = Parser::parse_line($handle, false, "/(.+:.+)\n/");

            if (isset($content) && count($content)) {
                foreach ($content as $value) {
                    $result = explode(":", $value);
                    $timestamps[$result[0]] = $result[1];
                }
            }

            if (isset($timestamps[$ver])) {
                if ($timestamps[$ver] + $days < time()) {
                    return $timestamps[$ver];
                } elseif ($return_time_stamp) {
                    return $timestamps[$ver];
                }
            }
        }
        return null;
    }

    /**
     * @param $ver
     */
    private function fix_time_stamp($ver)
    {
        $fn = Tools::ds(Config::get('log_dir'), SUCCESSFUL_TIMESTAMP);
        $timestamps = array();

        if (file_exists($fn)) {
            $handle = file_get_contents($fn);
            $content = Parser::parse_line($handle, false, "/(.+:.+)\n/");

            if (isset($content) && count($content)) {
                foreach ($content as $value) {
                    $result = explode(":", $value);
                    $timestamps[$result[0]] = $result[1];
                }
            }
        }

        $timestamps[$ver] = time();
        @unlink($fn);

        foreach ($timestamps as $key => $name)
            Log::write_to_file(SUCCESSFUL_TIMESTAMP, "$key:$name\r\n");
    }

    /**
     * @param $ver
     * @param $size
     */
    private function set_datebase_size($ver, $size)
    {
        $fn = Tools::ds(Config::get('log_dir'), DATABASES_SIZE);
        $sizes = array();

        if (file_exists($fn)) {
            $handle = file_get_contents($fn);
            $content = Parser::parse_line($handle, false, "/(.+:.+)\n/");

            if (isset($content) && count($content)) {
                foreach ($content as $value) {
                    $result = explode(":", $value);
                    $sizes[$result[0]] = $result[1];
                }
            }
        }

        $sizes[$ver] = $size;
        @unlink($fn);

        foreach ($sizes as $key => $name)
            Log::write_to_file(DATABASES_SIZE, "$key:$name\r\n");
    }

    /**
     * @return array|null
     */
    private function get_datebases_size()
    {
        $fn = Tools::ds(Config::get('log_dir'), DATABASES_SIZE);
        $sizes = array();

        if (file_exists($fn)) {
            $handle = file_get_contents($fn);
            $content = Parser::parse_line($handle, false, "/(.+:.+)\n/");

            if (isset($content) && count($content)) {
                foreach ($content as $value) {
                    $result = explode(":", $value);
                    $sizes[$result[0]] = $result[1];
                }
            }
        }

        return (!empty($sizes)) ? $sizes : null;
    }

    /**
     * @param string $directory
     * @return array
     */
    static private function get_all_patterns($directory = PATTERN)
    {
        $d = dir($directory);
        static $ar_patterns = array();

        while (false !== ($entry = $d->read())) {
            if (($entry == '.') || ($entry == '..'))
                continue;

            if (is_dir(Tools::ds($directory, $entry))) {
                static::get_all_patterns(Tools::ds($directory, $entry));
                continue;
            }

            $ar_patterns[] = Tools::ds($directory, $entry);
        }

        $d->close();
        return $ar_patterns;
    }

    /**
     * @param $key
     * @return bool
     */
    private function validate_key($key)
    {
        $result = explode(":", $key);
        Log::write_log(Language::t("Validating key [%s:%s]", $result[0], $result[1]), 4);
        $ret = Mirror::test_key($result[0], $result[1]);

        if (is_bool($ret)) {
            if ($ret) {
                $date = Mirror::exp_nod($result[0], $result[1]);
                Log::write_log(Language::t("Found valid key [%s:%s] Expiration date %s", $result[0], $result[1], $date), 4);
                if ($this->key_exists_in_file($result[0], $result[1], Tools::ds(Config::get('log_dir'), KEY_FILE_VALID)) == false) {
                    $this->write_key($result[0], $result[1], $date, KEY_FILE_VALID);
                } else {
                    Log::write_log("Key [$result[0]:$result[1]:$date] already exists", 4);
                }
                return true;
            } else {
                Log::write_log(Language::t("Invalid key [%s:%s]", $result[0], $result[1]), 4);

                if (Config::get('remove_invalid_keys') == 1 &&
                    $this->key_exists_in_file($result[0], $result[1], Tools::ds(Config::get('log_dir'), KEY_FILE_VALID))
                )
                    $this->delete_key($result[0], $result[1]);
            }
        } else {
            Log::write_log(Language::t("Unhandled exception [%s]", $ret), 4);
        }
        return false;
    }

    /**
     * @return array|null
     */
    private function read_keys()
    {
        if (!file_exists(Tools::ds(Config::get('log_dir'), KEY_FILE_VALID))) {
            $h = fopen(Tools::ds(Config::get('log_dir'), KEY_FILE_VALID), 'w');
            fclose($h);
        }

        $keys = Parser::parse_keys(Tools::ds(Config::get('log_dir'), KEY_FILE_VALID));

        if (!isset($keys) || !count($keys)) {
            Log::write_log(Language::t("Keys file is empty!"), 4);
            return null;
        }

        foreach ($keys as $value) {
            if ($this->validate_key($value))
                return explode(":", $value);
        }

        Log::write_log(Language::t("No working keys were found!"), 4);
        return null;
    }

    /**
     * @param $login
     * @param $password
     * @param $date
     * @param string $keyfile
     */
    private function write_key($login, $password, $date, $keyfile = KEY_FILE_VALID)
    {
        Log::write_to_file($keyfile, "$login:$password:$date\r\n");
    }

    /**
     * @param $login
     * @param $password
     */
    static private function delete_key($login, $password)
    {
        Parser::delete_parse_line_in_file($login . ':' . $password, Tools::ds(Config::get('log_dir'), KEY_FILE_VALID));
    }

    /**
     * @param $login
     * @param $passwd
     * @param $file
     * @return bool
     */
    static private function key_exists_in_file($login, $passwd, $file)
    {
        if (file_exists($file)) {
            $keys = Parser::parse_keys($file);

            if (isset($keys) && count($keys)) {
                foreach ($keys as $value) {
                    $result = explode(":", $value);

                    if ($result[0] == $login && $result[1] == $passwd)
                        return true;
                }
            }
        }

        return false;
    }

    /**
     * @param $url
     * @return null
     */
    static public function get_url_mime_type($url)
    {
        $header = @get_headers($url, 1);
        return isset($header['Content-Type']) ? $header['Content-Type'] : null;
    }

    /**
     * @param $search
     * @return string
     */
    static private function strip_tags_and_css($search)
    {
        $document = array(
            "'<script[^>]*?>.*?<\/script>'si",
            "'<[\/\!]*?[^<>]*?>'si",
            "'([\r\n])[\s]+'",
            "'&(quot|#34);'i",
            "'&(amp|#38);'i",
            "'&(lt|#60);'i",
            "'&(gt|#62);'i",
            "'&(nbsp|#160);'i",
            "'&(iexcl|#161);'i",
            "'&(cent|#162);'i",
            "'&(pound|#163);'i",
            "'&(copy|#169);'i",
            "'&#(\d+);'e"
        );
        $replace = array(
            "",
            "",
            "\\1",
            "\"",
            "&",
            "<",
            ">",
            " ",
            chr(161),
            chr(162),
            chr(163),
            chr(169),
            "chr(\\1)"
        );
        return trim(preg_replace($document, $replace, $search));
    }

    /**
     * @param $this_link
     * @param $level
     * @param $pattern
     * @return bool
     */
    private function parse_www_page($this_link, $level, $pattern)
    {
        static $found_key = false;
        $options = array(
            'http' => array(
                'method' => "GET",
                'header' => "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.1; rv:2.2) Gecko/20110201\r\n"
            )
        );
        $context = stream_context_create($options);
        $search = @file_get_contents($this_link, false, $context);
        $test = false;

        if (empty($http_response_header))
            $test = true;

        $header = Parser::parse_header($http_response_header);

        if (strlen($search) == 0 or empty($header[0]) or empty($header['Content-Type']) or !preg_match("/200/", $header[0]) or !preg_match("/text/", $header['Content-Type']))
            $test = true;

        if ($test) {
            Log::write_log(Language::t("Link wasn't found [%s]", $this_link), 4);
            return false;
        }

        Log::write_log(Language::t("Link was found [%s]", $this_link), 4);
        $login = array();
        $passwd = array();

        if (Config::get('debug_html') == 1) {
            $path_info = pathinfo($this_link);
            $dir = Tools::ds(Config::get('log_dir'), DEBUG_DIR, $path_info['basename']);
            @mkdir($dir, 0755, true);
            $filename = Tools::ds($dir, $path_info['filename'] . ".log");
            file_put_contents($filename, $this->strip_tags_and_css($search));
        }

        foreach ($pattern as $key)
            Parser::parse_template($search, $key, $login, $passwd);

        if (count($login) > 0) {
            Log::write_log(Language::t("Found keys: %s", count($login)), 3);

            for ($b = 0; $b < count($login); $b++) {
                if (preg_match("/script|googleuser/i", $passwd[$b]) and
                    $this->key_exists_in_file($login[$b], $passwd[$b], Tools::ds(Config::get('log_dir'), KEY_FILE_VALID))
                )
                    continue;

                if ($this->validate_key($login[$b].':'.$passwd[$b]) &&
                    count(file(Tools::ds(Config::get('log_dir'), KEY_FILE_VALID))) >= Config::get('count_find_keys')
                ) {
                    $found_key = true;
                    return true;
                }
            }
        }

        if ($level > 1) {
            $links = array();
            preg_match_all('/href *= *"([^\s"]+)/', $search, $results);

            foreach ($results[1] as $result) {
                str_replace('webcache.googleusercontent.com/search?q=cache:', '', $result);

                if (!preg_match("/youtube.com|ocialcomments.org/", $result)) {
                    preg_match('/https?:\/\/(?(?!\&amp).)*/', $result, $res);

                    if (!empty($res[0]))
                        $links[] = $res[0];
                }
            }
            Log::write_log(Language::t("Found links: %s", count($links)), 3);

            foreach ($links as $url) {
                $this->parse_www_page($url, $level - 1, $pattern);

                if ($found_key)
                    return true;
            }
        }

        return false;
    }

    /**
     * @return null
     */
    private function find_keys()
    {
        if (Config::get('find_auto_enable') != 1)
            return null;

        if (Config::get('find_system') === null) {
            $patterns = $this->get_all_patterns();
            shuffle($patterns);
        } else {
            $patterns = array(PATTERN . Config::get('find_system') . '.pattern');
        }

        while ($elem = array_shift($patterns)) {
            $pattern_name = pathinfo($elem);
            Log::write_log(Language::t("Begining search at %s", $pattern_name['basename']), 4);
            $find = @file_get_contents($elem);

            if (!$find) {
                Log::write_log(Language::t("File %s doesn't exist!", $pattern_name['basename']), 4);
                continue;
            }

            $link = Parser::parse_line($find, "link");
            $pageindex = Parser::parse_line($find, "pageindex");
            $pattern = Parser::parse_line($find, "pattern");
            $page_qty = Parser::parse_line($find, "page_qty");
            $recursion_level = Parser::parse_line($find, "recursion_level");

            if (empty($link)) {
                Log::write_log(Language::t("[link] doesn't set up in %s file!", $elem), 4);
                continue;
            }

            if (empty($pageindex))
                $pageindex[] = Config::get('default_pageindex');

            if (empty($pattern))
                $pattern[] = Config::get('default_pattern');

            if (empty($page_qty))
                $page_qty[] = Config::get('default_page_qty');

            if (empty($recursion_level))
                $recursion_level[] = Config::get('default_recursion_level');

            $queries = explode(", ", Config::get('default_search_query'));
            $found = false;

            foreach ($queries as $query) {
                $pages = substr_count($link[0], "#PAGE#") ? $page_qty[0] : 1;

                for ($i = 0; $i < $pages; $i++) {
                    $this_link = str_replace("#QUERY#", str_replace(" ", "+", trim($query)), $link[0]);
                    $this_link = str_replace("#PAGE#", ($i * $pageindex[0]), $this_link);

                    if ($this->parse_www_page($this_link, $recursion_level[0], $pattern) == true) {
                        $found = true;
                        break;
                    }
                }

                if ($found)
                    break;
            }

            if ($found)
                break;
        }

        return null;
    }

    /**
     * @param $time_run_script
     */
    private function generate_html($time_run_script)
    {
        Log::write_log(Language::t("Generating html..."), 0);
        $total_size = $this->get_datebases_size();
        $html_page = '';

        if (Config::get('generate_only_table') == '0') {
            $html_page .= '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
            $html_page .= '<html>';
            $html_page .= '<head>';
            $html_page .= '<title>' . Language::t("ESET NOD32 update server") . '</title>';
            $html_page .= '<meta http-equiv="Content-Type" content="text/html; charset=' . Config::get('html_codepage').'">';
            $html_page .= '<style type="text/css">html,body{height:100%;margin:0;padding:0;width:100%}table#center{border:0;height:100%;width:100%}table td table td{text-align:center;vertical-align:middle;font-weight:bold;padding:10px 15px;border:0}table tr:nth-child(odd){background:#eee}table tr:nth-child(even){background:#fc0}</style>';
            $html_page .= '</head>';
            $html_page .= '<body>';
            $html_page .= '<table id="center">';
            $html_page .= '<tr>';
            $html_page .= '<td align="center">';
        }

        $html_page .= '<table>';
        $html_page .= '<tr><td colspan="4">' . Language::t("ESET NOD32 update server") . '</td></tr>';
        $html_page .= '<tr>';
        $html_page .= '<td></td>';
        $html_page .= '<td>' . Language::t("Database version") . '</td>';
        $html_page .= '<td>' . Language::t("Database size") . '</td>';
        $html_page .= '<td>' . Language::t("Last update") . '</td>';
        $html_page .= '</tr>';

        global $DIRECTORIES;

        foreach ($DIRECTORIES as $ver => $dir) {
            if (Config::upd_version_is_set($ver) == '1') {
                $update_ver = Tools::ds(Config::get('web_dir'), $dir, 'update.ver');
                $version = Mirror::get_DB_version($update_ver);
                $timestamp = $this->check_time_stamp($ver, true);
                $html_page .= '<tr>';
                $html_page .= '<td>' . Language::t("Version %d", $ver) . '</td>';
                $html_page .= '<td>' . $version . '</td>';
                $html_page .= '<td>' . (isset($total_size[$ver]) ? Tools::bytesToSize1024($total_size[$ver]) : Language::t("n/a")) . '</td>';
                $html_page .= '<td>' . ($timestamp ? date("Y-m-d, H:i:s", $timestamp) : Language::t("n/a")) . '</td>';
                $html_page .= '</tr>';
            }
        }

        $html_page .= '<tr>';
        $html_page .= '<td colspan="2">' . Language::t("Present versions") . '</td>';
        $html_page .= '<td colspan="2">' . (Config::get('update_version_ess') ? 'EAV, ESS' : 'EAV') . '</td>';
        $html_page .= '</tr>';

        $html_page .= '<tr>';
        $html_page .= '<td colspan="2">' . Language::t("Present platforms") . '</td>';
        $html_page .= '<td colspan="2">' . ((Config::get('update_version_x32') ? '32bit' : '') . (Config::get('update_version_x64') ? (Config::get('update_version_x32') ? ', 64bit' : '64bit') : '')) . '</td>';
        $html_page .= '</tr>';

        $html_page .= '<tr>';
        $html_page .= '<td colspan="2">' . Language::t("Present languages") . '</td>';
        $html_page .= '<td colspan="2">' . Config::get('present_languages') . '</td>';
        $html_page .= '</tr>';

        $html_page .= '<tr>';
        $html_page .= '<td colspan="2">' . Language::t("Last execution of the script") . '</td>';
        $html_page .= '<td colspan="2">' . ($time_run_script ? date("Y-m-d, H:i:s", $time_run_script) : Language::t("n/a")) . '</td>';
        $html_page .= '</tr>';

        if ((Config::get('show_login_password')) and ($key !== null)) {
            if (file_exists(Tools::ds(Config::get('log_dir'), KEY_FILE_VALID))) {
                $keys = Parser::parse_keys(Tools::ds(Config::get('log_dir'), KEY_FILE_VALID));
                $key = (is_array($keys)) ? explode(":", $keys[0]) : null;
            }

            $html_page .= '<tr>';
            $html_page .= '<td colspan="2">' . Language::t("Used login") . '</td>';
            $html_page .= '<td colspan="2">' . $key[0] . '</td>';
            $html_page .= '</tr>';
            $html_page .= '<tr>';
            $html_page .= '<td colspan="2">' . Language::t("Used password") . '</td>';
            $html_page .= '<td colspan="2">' . $key[1] . '</td>';
            $html_page .= '</tr>';
            $html_page .= (isset($key[2])) ? '<tr><td colspan="2">' . Language::t("Expiration date") . '</td><td colspan="2">' . $key[2] . '</td></tr>' : '';
        }
        $html_page .= '</table>';
        $html_page .= (Config::get('generate_only_table') == '0') ? '</td></tr></table></body></html>' : '';
        $file = Tools::ds(Config::get('web_dir'), Config::get('filename_html'));

        if (file_exists($file))
            @unlink($file);

        Log::write_to_file($file, Tools::conv($html_page, Config::get('html_codepage')), true);
    }

    /**
     *
     */
    private function run_script()
    {
        $key = $this->read_keys();

        if ($key === null) {
            $this->find_keys();
            $key = $this->read_keys();

            if ($key === null) {
                Log::write_log(Language::t("No working keys were found!"), 1);
                Log::write_log(Language::t("The script has been stopped!"), 1);
                return;
            }
        }

        Mirror::find_best_mirrors($key);
        $mirrors = array();
        global $DIRECTORIES;

        foreach ($DIRECTORIES as $version => $dir) {
            $tmp_path = Tools::ds(Config::get('web_dir'), TMP_PATH, $dir);
            $cur_update_ver = Tools::ds(Config::get('web_dir'), $dir, 'update.ver');
            $tmp_update_ver = Tools::ds($tmp_path, 'update.ver');
            $old_version = Mirror::get_DB_version($cur_update_ver);

            if (Config::upd_version_is_set($version) == '1') {
                list($mirror, $new_version) = Mirror::check_mirror($version, $key);

                if ($mirror !== null) {
                    if (intval($old_version) >= intval($new_version)) {
                        Log::informer(Language::t("Your database is relevant %s", $old_version), $version, 2);
                    } else {
                        Log::write_log(Language::t("The latest database %s was found on %s", $new_version, $mirror), 2, $version);
                        $mirrors[$version] = array('mirror' => $mirror, 'old' => $old_version, 'new' => $new_version);

                        if (empty($GLOBALS['TESTKEY_REAL_PATH_NOD'])) {
                            $content = @file_get_contents($tmp_update_ver);
                            preg_match('#/[\w-]+/\w+/eav\w+\.nup#i', $content, $matches);

                            if (!empty($matches))
                                $GLOBALS['TESTKEY_REAL_PATH_NOD'] = trim($matches[0]);
                        }
                        if (empty($GLOBALS['TESTKEY_REAL_PATH_ESS'])) {
                            $content = @file_get_contents($tmp_update_ver);
                            preg_match('#/[\w-]+/\w+/ess\w+\.nup#i', $content, $matches);

                            if (!empty($matches))
                                $GLOBALS['TESTKEY_REAL_PATH_ESS'] = trim($matches[0]);
                        }
                    }
                } else {
                    Log::write_log(Language::t("All mirrors is down!"), 1, $version);
                }
            }
        }

        if (!empty($mirrors)) {
            $total_size = array();
            $total_downloads = array();
            $average_speed = array();

            foreach ($mirrors as $version => $mirror) {
                list($size, $downloads, $speed) = Mirror::download_signature($version, $mirror['mirror'], $key);
                $this->set_datebase_size($version, $size);

                if (is_null($downloads)) {
                    Log::informer(Language::t("Your database has not been updated!"), $version, 1);
                } else {
                    $total_size[$version] = $size;
                    $total_downloads[$version] = $downloads;
                    $average_speed[$version] = $speed;

                    if (empty($mirror['old'])) {
                        Log::informer(Language::t("Your database was successfully updated to %s", $mirror['new']), $version, 2);
                    } else {
                        Log::informer(Language::t("Your database was successfully updated from %s to %s", $mirror['old'], $mirror['new']), $version, 2);
                    }
                    $this->fix_time_stamp($version);
                }
            }

            Log::write_log(Language::t("Total size for all databases: %s", Tools::bytesToSize1024(array_sum($total_size))), 3);

            if (array_sum($total_downloads) > 0)
                Log::write_log(Language::t("Total downloaded for all databases: %s", Tools::bytesToSize1024(array_sum($total_downloads))), 3);

            if (array_sum($average_speed) > 0)
                Log::write_log(Language::t("Average speed for all databases: %s/s", Tools::bytesToSize1024(array_sum($average_speed) / count($average_speed))), 3);
        }

        if (Config::get('generate_html') == '1')
            $this->generate_html(Nod32ms::$start_time);
    }
}
