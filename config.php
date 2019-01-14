<?php
    Kirby::plugin('bnomei/redirects', [
        'options' => [
            'code' => 301,
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
        'snippets' => [
            'plugin-redirects' => __DIR__ . '/snippets/plugin-redirects.php',
        ],
        'pageMethods' => [
            'redirects' => function () {
                snippet('plugin-redirects');
            },
        ],
        'hooks' => [
            'route:before' => function () {
                snippet("plugin-redirects");
            },
        ],
        'routes' => [
            [
                'pattern' => 'plugin-redirects/codes',
                'method' => 'GET',
                'action' => function () {
                    $codes = \Bnomei\Redirects::codes();
                    return \Kirby\Http\Response::json(['codes' => $codes]);
                }
            ]
        ]
    ]);
