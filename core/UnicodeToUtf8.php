<?php

/**
 * Created by PhpStorm.
 * User: lyon
 * Date: 17-3-18
 * Time: 下午1:46
 */
/*class UnicodeToUtf8
{
    static function unicodeDecode($data)
    {
        function replace_unicode_escape_sequence($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }

        $rs = preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 'replace_unicode_escape_sequence', $data);

        return $rs;
    }
}*/

class UnicodeToUtf8
{
    static function unicodeDecode($data)
    {

        $rs = preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 'self::replace_unicode_escape_sequence', $data);

        return $rs;
    }
    public static function replace_unicode_escape_sequence($match) {
        return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
    }

    public static function remove($str)
    {
        return str_replace(['\\n', '\\t', '\\'], ['', '', ''], $str);
    }

    public static function uCurl($url)
    {
        $ch = curl_init($url);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt( $ch, CURLOPT_HEADER, 0);
        curl_setopt( $ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
