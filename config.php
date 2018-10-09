<?php
    Kirby::plugin('bnomei/redirects', [
        'options' => [
            'code' => 301,
            'map' => function () {
                $redirects = kirby()->site()->redirects();
                return $redirects->isEmpty() ? [] : $redirects->yaml();
            }, // array or closure
        ],
        'blueprints' => [
            'plugin-redirects' => __DIR__ . '/blueprints/sections/redirects.yml'
        ],
        'snippets' => [
            'plugin-redirects' => __DIR__ . '/snippets/plugin-redirects.php',
        ],
        'pageMethods' => [
            'redirects' => function () {
                snippet('plugin-redirects');
            },
        ],
        'routes' => [
            [
                'pattern' => 'plugin-redirects/codes',
                'method' => 'GET',
                'action' => function () {
                    $codes = [];
                    foreach (\Kirby\Http\Header::$codes as $code => $label) {
                        $codes[] = [
                            'code' => str_replace('_', '', $code),
                            'label' => $label,
                        ];
                    }
                    return \Kirby\Http\Response::json(['codes' => $codes]);
                }
            ]
        ]
    ]);
