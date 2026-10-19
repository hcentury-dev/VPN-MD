<?php

define('GOOGLE_API_KEY_JSON', '../account.json');

function getAccessToken() {
    $key = json_decode(file_get_contents(GOOGLE_API_KEY_JSON), true);
    $clientEmail = $key['client_email'];
    $privateKey = $key['private_key'];

    $url = 'https://oauth2.googleapis.com/token';
    $scope = 'https://www.googleapis.com/auth/firebase.messaging';

    $jwtHeader = base64_encode(json_encode([
        'alg' => 'RS256',
        'typ' => 'JWT'
    ]));

    $jwtPayload = base64_encode(json_encode([
        'iss' => $clientEmail,
        'scope' => $scope,
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => time(),
        'exp' => time() + 3600
    ]));

    $signature = '';
    openssl_sign($jwtHeader . '.' . $jwtPayload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    $jwtSignature = base64_encode($signature);

    $jwt = $jwtHeader . '.' . $jwtPayload . '.' . $jwtSignature;

    $response = file_get_contents($url, false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ])
        ]
    ]));

    $data = json_decode($response, true);
    return $data['access_token'];
}

function sendFCMNotification($to, $title, $body) {
    $accessToken = getAccessToken();

    $url = 'https://fcm.googleapis.com/v1/projects/dornanotif/messages:send';

    $fields = [
        'message' => [
            'token' => $to,
            'notification' => [
                'title' => $title,
                'body' => $body
            ]
        ]
    ];

    $headers = [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ];

    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => implode("\r\n", $headers),
            'content' => json_encode($fields)
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    return json_decode($response, true);
}

$deviceTokens = [
    'cjwyZCenTRiZbKF_AcEtbs:APA91bFn_Zbp7KhGDm0nmRE7C-C1J2Ahrz_4rfF1rNPJFsXsmTTsrbz9Pk9L6cB4o7Ze_dAj7p7iUNx2_GaOSVE5s-snOxQZzMdmbUmkY_WNzXvyGqFfr8c',
    '1111',
    '2222222'
];

$title = 'Title test';
$body = 'ssssssssssss';

foreach ($deviceTokens as $deviceToken) {
    $response = sendFCMNotification($deviceToken, $title, $body);
    print_r($response); // پاسخ هر دستگاه را نمایش می‌دهد
}


?>
