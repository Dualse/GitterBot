<?php
return [
    // Gitter API Token
    'token' => env('GITTER_TOKEN'),

    // Enable gitter responses
    'output' => true,

    // Room aliases
    'rooms' => [
        'bot.debug'     => '56019a060fc9f982beb17a5e', // https://gitter.im/KarmaBot/KarmaTest

        'laravel.chat'  => '52f9b90e5e986b0712ef6b9d', // https://gitter.im/LaravelRUS/chat
        'laravel.site'  => '54053e51163965c9bc201c26', // https://gitter.im/LaravelRUS/laravel.ru
        'laravel.bot'   => '560281040fc9f982beb1908a', // https://gitter.im/LaravelRUS/GitterBot

        'yii.offtop'    => '55dc21c10fc9f982beae822c', // https://gitter.im/yiisoft/yii2/offtopic-rus
        'yii.chat'      => '555086c915522ed4b3e03631', // https://gitter.im/yiisoft/yii2/rus

        'jphp.chat'     => '550177ff15522ed4b3dd296e', // https://gitter.im/jphp-compiler/jphp

        'drupal.site'   => '565c8d6716b6c7089cbcbd5d', // https://gitter.im/DrupalRu/drupal.ru
    ]
];
