<?php

return [
    'elasticsearch' => [
        'hosts'     => ['127.0.0.1:9200'],
        'db_name'   => 'sm_stats',
        'options'   => []
    ],
    'twitter' => [
        'api_key'               => '_api_key_',
        'api_secret'            => '_api_secret_',
        'access_token'          => '_access_token_',
        'access_token_secret'   => '_access_token_secret_'
    ],
    'debug_file'    => dirname(__DIR__) . '/logs/debug.log',
    'red_file'      => dirname(__DIR__) . '/logs/important.log',
    'blue_file'     => dirname(__DIR__) . '/logs/app.log'
];