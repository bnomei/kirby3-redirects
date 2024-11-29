<?php

use Kirby\Filesystem\F;

return [
    'debug' => true,
    'editor' => 'phpstorm',

    'hooks' => [
        'redirect:before' => function (int $code, \Bnomei\Redirect $redirect) {
            // do whatever you need, like...
            F::write(kirby()->root('logs').'/redirect.log', implode(' ', [
                '['.date('Y-m-d H:i:s').']',
                $redirect->code().'.INFO',
                $redirect->from(),
                $redirect->to(),
                PHP_EOL,
            ]), true);
        },
        '404:before' => function (\Kirby\Http\Route $route, string $path, string $method) {
            // do whatever you need, like...
            F::write(kirby()->root('logs').'/404.log', implode(' ', [
                '['.date('Y-m-d H:i:s').']',
                '404.ERROR',
                $method,
                $path,
                PHP_EOL,
            ]), true);
        },
    ],
];
