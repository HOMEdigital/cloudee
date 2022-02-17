<?php

return [
    'nextcloud' => [
        'url' => env('CLOUDEE_NEXTCLOUD_URL'),
        'params' => env('CLOUDEE_NEXTCLOUD_PARAMS'),
        'user' => env('CLOUDEE_NEXTCLOUD_USER'),
        'password' => env('CLOUDEE_NEXTCLOUD_PASSWORD'),
    ],
    'webdav' => [
        'url' => env('CLOUDEE_WEBDAV_URL'),
        'basePath' => env('CLOUDEE_WEBDAV_BASE_PATH'),
        'user' => env('CLOUDEE_WEBDAV_USER'),
        'password' => env('CLOUDEE_WEBDAV_PASSWORD'),
    ]
];
