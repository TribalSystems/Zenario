<?php

/*
 * Encodes a name value pair collection to a string of format 'name1=value1&name2=value2',
 * suitable to send as a querystring.
 */

class NVPCodec {
    /*
     * Returns NVP encoded string of all entries in input array
     */

    public static function Encode($input) {
        $output = "";

        $entries = array();

        foreach ($input as $key => $value) {
            $entries[$key] = sprintf("%s=%s", $key, urlencode($value));
        }

        return join("&", $entries);
    }

    /*
     * Takes an NVP encoded string and returns an associate array with the corresponding name-value pairs.
     */

    public static function Decode($input) {

        $entries = explode("&", $input);

        $output = array();

        foreach ($entries as $entry) {
            // entry should look like 'key=urlencodedsvalue'
            $temp = explode("=", $entry, 2);

            if (isset($temp[1])) {
                $output[$temp[0]] = urldecode($temp[1]);
            } else {
                $output[$temp[0]] = null;
            }
        }

        return $output;
    }

}

?>
