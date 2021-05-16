<?php

@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('bnomei/redirects', [
    'options' => [
        'code' => 301,
        'querystring' => true,
        'map' => function () {
            return kirby()->site()->redirects();
        }, // array, closure with structure-field or array
        'cache' => true,
    ],
    'blueprints' => [
        'plugin-redirects' => __DIR__ . '/blueprints/sections/redirects.yml',
        'plugin-redirects3xx' => __DIR__ . '/blueprints/sections/redirects3xx.yml',
    ],
    'hooks' => [
        'route:before' => function () {
            $isPanel = strpos(
                kirby()->request()->url()->toString(),
                kirby()->urls()->panel()
            ) !== false;
            $isApi = strpos(
                kirby()->request()->url()->toString(),
                kirby()->urls()->api()
            ) !== false;
            if (!$isPanel && !$isApi) {
                \Bnomei\Redirects::singleton()->redirect();
            }
        },
    ],
    'siteMethods' => [
        'appendRedirects' => function ($data) {
            return \Bnomei\Redirects::singleton()->append($data);
        },
        'removeRedirects' => function ($data) {
            return \Bnomei\Redirects::singleton()->remove($data);
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
