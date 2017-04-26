<?php

/**
 * Class Tools
 */
class Tools
{

    /**
     * @param $source
     * @param $dest
     * @param int $buffer
     * @return array|bool
     */
    static public function download_file($source, $dest, $buffer = 1024)
    {
        ini_set('default_socket_timeout', CONNECTTIMEOUT);
        $header = @get_headers($source, 1);
        ini_restore('default_socket_timeout');

        if (is_array($header)) {
            if (preg_match("/200/", $header[0])) {
                $dir = dirname($dest);

                if (!file_exists($dir))
                    @mkdir($dir, 0755, true);

                $in = fopen($source, 'rb', false);
                $out = fopen($dest, "wb");

                while ($chunk = fread($in, $buffer))
                    fwrite($out, $chunk, $buffer);

                fclose($in);
                fclose($out);
            }

            return $header;
        } else
            return false;
    }

    /**
     * @return string
     */
    static public function get_archive_extension()
    {
        return ".gz";
    }

    /**
     *
     */
    static public function archive_file()
    {
        $log = self::ds(Config::get("log_dir"), LOG_FILE);
        $fp = gzopen($log . ".1.gz", 'w9');
        gzwrite($fp, file_get_contents($log));
        gzclose($fp);
        unlink($log);
    }

    /**
     * @param $source
     * @param $dest
     */
    static public function extract_file($source, $dest)
    {
        switch (PHP_OS) {
            case "Darwin":
            case "Linux":
            case "FreeBSD":
            case "OpenBSD":
            default:
                system(sprintf("`/usr/bin/which unrar` x -inul -y %s %s", $source, $dest));
                break;
            case "WINNT":
                shell_exec(sprintf(TOOLS . "unrar.exe e -y %s %s", $source, $dest));
                break;
        }
    }

    /**
     * @param $hostname
     * @param int $port
     * @return bool
     */
    static public function ping($hostname, $port = 80)
    {
        if ($fs = @fsockopen($hostname, $port, $errno, $errstr, CONNECTTIMEOUT)) {
            fclose($fs);
            return true;
        } else
            return false;
    }

    /**
     * @param $bytes
     * @param int $precision
     * @return string
     */
    static public function bytesToSize1024($bytes, $precision = 2)
    {
        $unit = array('Bytes', 'KBytes', 'MBytes', 'GBytes', 'TBytes', 'PBytes', 'EBytes');
        return @round(
                $bytes / pow(1024, ($i = floor(log($bytes, 1024)))), $precision
            ) . ' ' . $unit[intval($i)];
    }

    /**
     * @param $secs
     * @return false|string
     */
    static public function secondsToHumanReadable($secs)
    {
        return ($secs > 60 * 60 * 24) ? gmdate("H:i:s", $secs) : gmdate("i:s", $secs);
    }

    /**
     * @return mixed
     */
    static public function ds()
    {
        return preg_replace('/[\/\\\\]+/', DIRECTORY_SEPARATOR, implode('/', func_get_args()));
    }

    /**
     * @param $text
     * @param $to_encoding
     * @return mixed|string
     */
    static public function conv($text, $to_encoding)
    {
        if (preg_match("/utf-8/i", $to_encoding))
            return $text;
        elseif (function_exists('mb_convert_encoding'))
            return mb_convert_encoding($text, 'UTF-8', $to_encoding);
        elseif (function_exists('iconv'))
            return iconv('UTF-8', $to_encoding, $text);
        else {
            $conv = array();

            for ($x = 128; $x <= 143; $x++) {
                $conv['u'][] = chr(209) . chr($x);
                $conv['w'][] = chr($x + 112);

            }

            for ($x = 144; $x <= 191; $x++) {
                $conv['u'][] = chr(208) . chr($x);
                $conv['w'][] = chr($x + 48);
            }

            $conv['u'][] = chr(208) . chr(129);
            $conv['w'][] = chr(168);
            $conv['u'][] = chr(209) . chr(145);
            $conv['w'][] = chr(184);
            $conv['u'][] = chr(208) . chr(135);
            $conv['w'][] = chr(175);
            $conv['u'][] = chr(209) . chr(151);
            $conv['w'][] = chr(191);
            $conv['u'][] = chr(208) . chr(134);
            $conv['w'][] = chr(178);
            $conv['u'][] = chr(209) . chr(150);
            $conv['w'][] = chr(179);
            $conv['u'][] = chr(210) . chr(144);
            $conv['w'][] = chr(165);
            $conv['u'][] = chr(210) . chr(145);
            $conv['w'][] = chr(180);
            $conv['u'][] = chr(208) . chr(132);
            $conv['w'][] = chr(170);
            $conv['u'][] = chr(209) . chr(148);
            $conv['w'][] = chr(186);
            $conv['u'][] = chr(226) . chr(132) . chr(150);
            $conv['w'][] = chr(185);
            $win = str_replace($conv['u'], $conv['w'], $text);

            if (preg_match("/1251/i", $to_encoding))
                return $win;
            elseif (preg_match("/koi8/i", $to_encoding))
                return convert_cyr_string($win, 'w', 'k');
            elseif (preg_match("/866/i", $to_encoding))
                return convert_cyr_string($win, 'w', 'a');
            elseif (preg_match("/mac/i", $to_encoding))
                return convert_cyr_string($win, 'w', 'm');
            else
                return $text;
        }
    }

    /**
     * @param $resource
     * @return bool|mixed
     */
    static public function get_resource_id($resource)
    {
        return (!is_resource($resource)) ? false : @end(explode('#', (string)$resource));
    }
}
