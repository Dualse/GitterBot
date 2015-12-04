<?php

return [
    // Gitter API Token
    'token' => env('GITTER_TOKEN'),

    // Enable gitter responses
    'output' => true,

    'middlewares' => [
        'common' => [
            App\Gitter\Middleware\GoogleSearchMiddleware::class
        ],
        'laravel' => [

        ]
    ],

    // Room aliases
    'rooms' => [
        'debug'         => [
            'id' => '56019a060fc9f982beb17a5e', // https://gitter.im/KarmaBot/KarmaTest
            'middlewares' => ['common', 'laravel'],
        ],

        'laravel.chat'  => [
            'id' => '52f9b90e5e986b0712ef6b9d', // https://gitter.im/LaravelRUS/chat
            'middlewares' => ['common', 'laravel'],
        ],

        'laravel.site'  => [
            'id' => '54053e51163965c9bc201c26', // https://gitter.im/LaravelRUS/laravel.ru
            'middlewares' => ['common', 'laravel'],
        ],

        'laravel.bot'   => [
            'id' => '560281040fc9f982beb1908a', // https://gitter.im/LaravelRUS/GitterBot
            'middlewares' => ['common', 'laravel'],
        ],

        'yii.offtop'    => [
            'id' => '55dc21c10fc9f982beae822c', // https://gitter.im/yiisoft/yii2/offtopic-rus
            'middlewares' => ['common'],
        ],

        'yii.chat'      => [
            'id' => '555086c915522ed4b3e03631', // https://gitter.im/yiisoft/yii2/rus
            'middlewares' => ['common'],
        ],

        'jphp.chat'     => [
            'id' => '550177ff15522ed4b3dd296e', // https://gitter.im/jphp-compiler/jphp
            'middlewares' => ['common'],
        ],

        'drupal.site'   => [
            'id' => '565c8d6716b6c7089cbcbd5d', // https://gitter.im/DrupalRu/drupal.ru
            'middlewares' => ['common'],
        ],
    ]
];
