<?php

namespace Volistx\FrameworkKernel\Helpers;

class GeoPoint
{
    public static function Lookup($ip)
    {
        $url = 'https://geopoint.api.volistx.io/'.$ip;

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);

        $headers = [
            'Authorization: Bearer '.config('volistx.geopoint_api_key'),
        ];
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $resp = curl_exec($curl);

        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
        }

        curl_close($curl);

        if (!isset($error_msg) && $resp != null) {
            return json_decode($resp, true);
        } else {
            return null;
        }
    }
}
