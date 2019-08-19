<?php

@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('bnomei/redirects', [
    'options' => [
        'code' => 301,
        'querystring' => true,
        'map' => function () {
            $redirects = kirby()->site()->redirects();
            return $redirects->isEmpty() ? [] : $redirects->yaml();
        }, // array or closure
        'cache' => true,
    ],
    'blueprints' => [
        'plugin-redirects' => __DIR__ . '/blueprints/sections/redirects.yml',
        'plugin-redirects3xx' => __DIR__ . '/blueprints/sections/redirects3xx.yml'
    ],
    'hooks' => [
        'route:before' => function () {
            \Bnomei\Redirects::redirects();
        },
    ],
    'routes' => [
        [
            'pattern' => 'plugin-redirects/codes',
            'method' => 'GET',
            'action' => function () {
                $codes = \Bnomei\Redirects::codes();
                return \Kirby\Http\Response::json(['codes' => $codes]);
            },
        ],
    ],
]);
