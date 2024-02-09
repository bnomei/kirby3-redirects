<?php

use Kirby\Http\Route;

@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('bnomei/redirects', [
    'options' => [
        'code' => 301,
        'querystring' => true,
        'map' => function () {
            return kirby()->site()->redirects();
        }, // array, closure with structure-field or array
        'block' => [
            'enabled' => true,
            // catch most basic attacks early
            'wordpress' => [
                ['fromuri' => 'wp-login.php', 'touri' => '', 'code' => 404],
                ['fromuri' => 'wp-admin', 'touri' => '', 'code' => 404],
                ['fromuri' => 'xmlrpc.php', 'touri' => '', 'code' => 404],
                ['fromuri' => 'wp-content\/plugins\/.*', 'touri' => '', 'code' => 404],
                ['fromuri' => 'wp-content\/themes\/.*', 'touri' => '', 'code' => 404],
                ['fromuri' => 'wp-includes\/.*', 'touri' => '', 'code' => 404],
                ['fromuri' => 'wp-config.php', 'touri' => '', 'code' => 404],
                ['fromuri' => 'wp-admin/admin-ajax.php', 'touri' => '', 'code' => 404],
                ['fromuri' => 'wp-json\/wp\/v2\/.*', 'touri' => '', 'code' => 404],
                ['fromuri' => 'xmlrpc.php?action=pingback.ping', 'touri' => '', 'code' => 404],
            ],
            'joomla' => [
                ['fromuri' => 'administrator/index.php', 'touri' => '', 'code' => 404],
                ['fromuri' => 'administrator\/components\/.*', 'touri' => '', 'code' => 404],
                ['fromuri' => 'components\/com_users\/.*', 'touri' => '', 'code' => 404],
                ['fromuri' => 'components\/com_content\/.*', 'touri' => '', 'code' => 404],
                ['fromuri' => 'components\/com_banners\/.*', 'touri' => '', 'code' => 404],
                // ['fromuri' => 'administrator\/components\/com_joomlaupdate\/.*', 'touri' => '', 'code' => 404],
                // ['fromuri' => 'administrator\/components\/com_admin\/.*', 'touri' => '', 'code' => 404],
            ],
            'drupal' => [
                ['fromuri' => 'user/login', 'touri' => '', 'code' => 404],
                ['fromuri' => 'user/register', 'touri' => '', 'code' => 404],
                ['fromuri' => 'admin\/.*', 'touri' => '', 'code' => 404], // catch-all (1)
                // ['fromuri' => 'admin/config', 'touri' => '', 'code' => 404],
                // ['fromuri' => 'admin/structure', 'touri' => '', 'code' => 404],
                // ['fromuri' => 'admin/people', 'touri' => '', 'code' => 404],
                // ['fromuri' => 'admin/modules', 'touri' => '', 'code' => 404],
                ['fromuri' => 'sites\/default\/files\/.*', 'touri' => '', 'code' => 404],
                ['fromuri' => 'sites/default/settings.php', 'touri' => '', 'code' => 404],
            ],
            'magento' => [
                // ['fromuri' => 'admin\/.*', 'touri' => '', 'code' => 404], // catch-all (1)
                ['fromuri' => 'downloader\/.*', 'touri' => '', 'code' => 404],
                // ['fromuri' => 'api\/.*', 'touri' => '', 'code' => 404], // Kirby API
                ['fromuri' => 'app/etc/local.xml', 'touri' => '', 'code' => 404],
                ['fromuri' => 'app/etc/config.xml', 'touri' => '', 'code' => 404],
                ['fromuri' => 'var/export\/.*', 'touri' => '', 'code' => 404],
                ['fromuri' => 'var/log\/.*', 'touri' => '', 'code' => 404],
                ['fromuri' => 'var/report\/.*', 'touri' => '', 'code' => 404],
                ['fromuri' => 'downloader\/Maged\/.*', 'touri' => '', 'code' => 404],
            ],
            'shopify' => [
                // see catch-all (1)
                // ['fromuri' => 'admin/auth/login', 'touri' => '', 'code' => 404],
                // ['fromuri' => 'admin\/settings\/.*', 'touri' => '', 'code' => 404],
                // ['fromuri' => 'admin\/products\/.*', 'touri' => '', 'code' => 404],
                // ['fromuri' => 'admin\/orders\/.*', 'touri' => '', 'code' => 404],
                // ['fromuri' => 'admin\/themes\/.*', 'touri' => '', 'code' => 404],
                // ['fromuri' => 'admin\/apps\/.*', 'touri' => '', 'code' => 404],
                // ['fromuri' => 'admin\/charges\/.*', 'touri' => '', 'code' => 404],
            ],
        ],
        'cache' => true,
    ],
    'blueprints' => [
        // 'plugin-redirects' => __DIR__ . '/blueprints/sections/redirects.yml',
        'plugin-redirects' => require_once __DIR__ . '/blueprints/sections/redirects.php',
        'plugin-redirects3xx' => __DIR__ . '/blueprints/sections/redirects3xx.yml',
    ],
    'hooks' => [
        'route:after' => function (Route $route, string $path, string $method, $result, bool $final) {
            if ($final === true && empty($result) === true) {
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
