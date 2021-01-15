<?php

namespace Waiyanhein\LumenSignedUrl;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class URLSigner
{
    static $FILENAME = 'generated-signed-urls.txt';
    static $STORAGE_DISK = 'local';

    //$expireAt should be string in Y-m-d H:i:s format
    //$params is for passing the additional parameters
    public static function sign($url, $expireAt, $params = [ ]): ?string
    {
        if (empty($url)) {
            return null;
        }

        if (empty($expireAt)) {
            return null;
        }

        $millsNow = (string)time();
        $signature = sha1($url . $expireAt . $millsNow) . uniqid() . uniqid();
        $encodedExpireAt = base64_encode($expireAt);
        $signedUrl = "{$url}?signature={$signature}&expireAt={$encodedExpireAt}";
        if (count($params) > 0) {
            foreach ($params as $key => $value) {
                $signedUrl = "{$signedUrl}&{$key}={$value}";
            }
        }
        $signedUrlData = [
            'expire_at' => $expireAt,
            'signature' => $signature,
            'url' => $url,
            'signed_url' => $signedUrl,
            'params' => $params,
        ];

        $existingFileContent = '';
        if (Storage::exists(static::$FILENAME)) {
            $existingFileContent = Storage::disk(static::$STORAGE_DISK)->get(static::$FILENAME);
        }
        $signedUrlDataList = [];
        if ($existingFileContent) {
            try {
                $signedUrlDataList = json_decode($existingFileContent);
            } catch (\Exception $e) {

            }
        }

        $signedUrlDataList[] = $signedUrlData;
        Storage::disk(static::$STORAGE_DISK)->put(static::$FILENAME, json_encode($signedUrlDataList));

        return $signedUrl;
    }

    public static function validate($signedUrl)
    {
        if (empty($signedUrl)) {
            return false;
        }

        $urlComponents = parse_url($signedUrl);
        if (! isset($urlComponents['query'])) {
            return false;
        }

        $params = [];
        parse_str($urlComponents['query'], $params);

        if (!(isset($params['expireAt']) && isset($params['signature']))) {
            return false;
        }

        try {
            $expireAt = base64_decode($params['expireAt']);
            $signature = $params['signature'];
            $fileContent = Storage::disk(static::$STORAGE_DISK)->get(static::$FILENAME);
            $signedUrlDataList = json_decode($fileContent);
            if (! $signedUrlDataList) {
                return false;
            }

            $signedUrlDataList = collect($signedUrlDataList);
            $signedUrl = $signedUrlDataList->where('signature', '=', $signature)->first();
            if (! $signedUrl) {
                return false;
            }
            if ($signedUrl->expire_at != $expireAt) {
                return false;
            }

            return Carbon::createFromFormat('Y-m-d H:i:s', $expireAt)->gte(Carbon::now());
        } catch (\Exception $exception) {
            return false;
        }
    }
}
