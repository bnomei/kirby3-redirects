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
        // 'plugin-redirects' => __DIR__ . '/blueprints/sections/redirects.yml',
        'plugin-redirects' => require_once __DIR__ . '/blueprints/sections/redirects.php',
        'plugin-redirects3xx' => __DIR__ . '/blueprints/sections/redirects3xx.yml',
    ],
    'hooks' => [
        'page.render:before' => function (string $contentType, array $data, Kirby\Cms\Page $page) {
            if ($page->isErrorPage()) {
                $isPanel = str_contains(kirby()->request()->url()->toString(), kirby()->urls()->panel());
                $isApi = str_contains(kirby()->request()->url()->toString(), kirby()->urls()->api());
                $isMedia = str_contains(kirby()->request()->url()->toString(), kirby()->urls()->media());
                if (!$isPanel && !$isApi && !$isMedia) {
                    \Bnomei\Redirects::singleton()->redirect();
                }
            }
        },
        'page.update:after' => function (Kirby\Cms\Page $newPage, Kirby\Cms\Page $oldPage) {
            $redirects = \Bnomei\Redirects::singleton();
            if ($redirects->getParent() && $redirects->getParent()->id() === $newPage->id()) {
                $redirects->flush();
            }
        },
        'site.update:after' => function (Kirby\Cms\Site $newSite, Kirby\Cms\Site $oldSite) {
            $redirects = \Bnomei\Redirects::singleton();
            if ($redirects->getParent() && $redirects->getParent()::class === $newSite::class) {
                $redirects->flush();
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
