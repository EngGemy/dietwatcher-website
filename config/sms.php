<?php

return [
    //sms provider = mshastra | connect
    'default' => env('SMS_PROVIDER', 'connect'),

    'mshastra' => [
        'url' => env('MSHASTRA_URL', 'https://mshastra.com/sendurl.aspx'),
        'user' => env('MSHASTRA_PROFILE_ID'),
        'password' => env('MSHASTRA_PASSWORD'),
        'sender_id' => env('MSHASTRA_SENDER_ID'),
    ],

    'connect' => [
        'url' => env('CONNECT_URL', 'https://sms.connectsaudi.com/sendurl.aspx'),
        'user' => env('CONNECT_PROFILE_ID'),
        'password' => env('CONNECT_PASSWORD'),
        'sender_id' => env('CONNECT_SENDER_ID', 'DietWatcher'),
    ],
];
