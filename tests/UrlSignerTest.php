<?php

use Waiyanhein\LumenSignedUrl\URLSigner;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class UrlSignerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (Storage::exists(URLSigner::$FILENAME)) {
            Storage::disk(URLSigner::$STORAGE_DISK)->delete(URLSigner::$FILENAME);
        }
    }

    public function testItCanGenerateSignedUrl()
    {
        $url = 'http://testing.com';
        $expireAt = Carbon::now()->addDays(5)->format('Y-m-d H:i:s');
        $signedUrl = URLSigner::sign($url, $expireAt);

        $this->assertNotEmpty($signedUrl);
        //test the file content to ensure that the meta data is correct.
        $fileContent = Storage::disk(URLSigner::$STORAGE_DISK)->get(URLSigner::$FILENAME);
        $signedUrlList = json_decode($fileContent);
        $this->assertEquals($expireAt, $signedUrlList[0]->expire_at);
        $this->assertNotEmpty($signedUrlList[0]->signature);
        $this->assertEquals($url, $signedUrlList[0]->url);
        $encodedExpireAt = base64_encode($expireAt);
        $this->assertEquals("{$url}?signature={$signedUrlList[0]->signature}&expireAt={$encodedExpireAt}", $signedUrlList[0]->signed_url);
    }

    public function testItCanGenerateSignedUrlWithParams()
    {
        $url = 'http://testing.com';
        $expireAt = Carbon::now()->addDays(5)->format('Y-m-d H:i:s');
        $params = [
            'first_name' => 'Wai',
            'last_name' => 'Hein',
        ];
        $signedUrl = URLSigner::sign($url, $expireAt, $params);

        $this->assertNotEmpty($signedUrl);
        //test the file content to ensure that the meta data is correct.
        $fileContent = Storage::disk(URLSigner::$STORAGE_DISK)->get(URLSigner::$FILENAME);
        $signedUrlList = json_decode($fileContent);
        $encodedExpireAt = base64_encode($expireAt);
        $this->assertEquals("{$url}?signature={$signedUrlList[0]->signature}&expireAt={$encodedExpireAt}&first_name=Wai&last_name=Hein", $signedUrlList[0]->signed_url);
        $this->assertEquals('Wai', $signedUrlList[0]->params->first_name);
        $this->assertEquals('Hein', $signedUrlList[0]->params->last_name);
    }

    public function testSignedUrlValidationPasses()
    {
        $url = 'http://testing.com';
        $expireAt = Carbon::now()->addSeconds(10)->format('Y-m-d H:i:s');
        $params = [
            'first_name' => 'Wai',
            'last_name' => 'Hein',
        ];
        $signedUrl = URLSigner::sign($url, $expireAt, $params);

        $this->assertTrue(URLSigner::validate($signedUrl));
    }

    public function testSignedUrlValidationFails()
    {
        $url = 'http://testing.com';
        $expireAt = Carbon::now()->subSeconds(5)->format('Y-m-d H:i:s');
        $params = [
            'first_name' => 'Wai',
            'last_name' => 'Hein',
        ];
        $signedUrl = URLSigner::sign($url, $expireAt, $params);

        $this->assertFalse(URLSigner::validate($signedUrl));
    }

    public function testItSavesMultipleSignedUrlsInStorage()
    {
        $urls = [
            [
                'url' => 'http://testing1.com',
                'expire_at' => Carbon::now()->addMinutes(5)->format('Y-m-d H:i:s'),
            ],
            [
                'url' => 'http://testing2.com',
                'expire_at' => Carbon::now()->addMinutes(3)->format('Y-m-d H:i:s'),
            ]
        ];

        $signedUrls = [];
        foreach ($urls as $url) {
            $signedUrls[] = URLSigner::sign($url['url'], $url['expire_at']);
        }

        $fileContent = Storage::disk(URLSigner::$STORAGE_DISK)->get(URLSigner::$FILENAME);
        $signedUrlList = json_decode($fileContent);
        $this->assertEquals(2, count($signedUrlList));
        $this->assertEquals($signedUrls[0], $signedUrlList[0]->signed_url);
        $this->assertEquals($signedUrls[1], $signedUrlList[1]->signed_url);
    }
}
