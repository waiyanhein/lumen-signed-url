### Package to generate signed url for Lumen framework

This package use Laravel file storage system to save the signed URLs, https://laravel.com/docs/8.x/filesystem.

##### Installation
`composer require waiyanhein/lumen-signed-url`

##### Generating temporary Signed URL
```
$signedUrl = URLSigner::sign("http://testing.com", Carbon::now()->addMinutes(10)->format('Y-m-d H:i:s'));
```
- Note: the date must be in `Y-m-d H:i:s` format.

##### Signing URL with parameters
If your URL has parameters you can pass them as the third parameter as array.
```
$signedUrl = URLSigner::sign("http://testing.com", Carbon::now()->addMinutes(10)->format('Y-m-d H:i:s'), [ 'first_name' = 'Wai', 'last_name' => 'Hein' ]);
```

##### Validating the Signed URL
```
$isValid = URLSigner::validate($signedUrl);
```
