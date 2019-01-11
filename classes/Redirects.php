<?php

namespace Bnomei;

class Redirects
{
    public static function redirects($options = [])
    {
        $statusCode = option('bnomei.redirects.code', \Kirby\Toolkit\A::get($options, 'code', 301));
        $map = option('bnomei.redirects.map', []);
        if (is_callable($map)) {
            $map = $map();
        }
        if (!is_array($map)) {
            $map = [];
        }
        $map = array_merge($map, \Kirby\Toolkit\A::get($options, 'map', []));
        $siteurl = site()->url();
        $sitebase = \Kirby\Http\Url::path($siteurl);
        $sitepath = strtok($_SERVER["REQUEST_URI"], '?');

        foreach ($map as $redirects) {
            $fromuri = \Kirby\Toolkit\A::get($redirects, 'fromuri');
            $fromuri = '/' . $sitebase . '/' . trim(str_replace($siteurl, '', $fromuri), '/');

            if ($fromuri != $sitepath) {
                continue;
            }

            $touri = '/' . trim(\Kirby\Toolkit\A::get($redirects, 'touri'), '/');

            if ($page = page($touri)) {
                $touri = $page->url();
            } else {
                $touri = url($touri);
            }
            $code = intval(\Kirby\Toolkit\A::get($redirects, 'code', $statusCode));
            if (!$code || $code == 0) {
                $code = $statusCode;
            }

            // REDIRECT
            \Kirby\Http\Header::redirect($touri, $code);
            break;
        }
    }
}
