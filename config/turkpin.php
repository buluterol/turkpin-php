<?php

return [
    // Turkpin bayi hesabı kullanıcı adı ve şifresi
    'username' => env('TURKPIN_USERNAME'),
    'password' => env('TURKPIN_PASSWORD'),

    // true: test ortamı (turkpin.net), false: canlı (turkpin.com)
    'test' => (bool) env('TURKPIN_TEST', false),

    // HTTP zaman aşımı (saniye)
    'timeout' => (int) env('TURKPIN_TIMEOUT', 30),
];
