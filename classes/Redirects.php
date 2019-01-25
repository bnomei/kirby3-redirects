<?php

namespace Bnomei;

class Redirects
{
    public static function redirects($options = [])
    {
        $statusCode = intval(str_replace('_', '', option('bnomei.redirects.code', \Kirby\Toolkit\A::get($options, 'code', 301))));
        $map = option('bnomei.redirects.map', []);
        if (is_callable($map)) {
            $map = $map();
        }
        if (!is_array($map)) {
            $map = [];
        }
        $map = array_merge($map, \Kirby\Toolkit\A::get($options, 'map', []));
        $siteurl = site()->url(); // a) www.example.com or b) www.example.com/subfolder
        $sitepath = option('bnomei.redirects.querystring') ? $_SERVER["REQUEST_URI"] : strtok($_SERVER["REQUEST_URI"], '?'); // / or /page or /subfolder or /subfolder/page
        $sitebase = \Kirby\Http\Url::path($siteurl, true, true);

        foreach ($map as $redirects) {
            $fromuri = \Kirby\Toolkit\A::get($redirects, 'fromuri');
            $fromuri = '/' . trim($sitebase . str_replace($siteurl, '', $fromuri), '/');

            if ($fromuri != $sitepath) {
                continue;
            }

            $touri = '/' . trim(\Kirby\Toolkit\A::get($redirects, 'touri'), '/');
            if ($page = page($touri)) {
                $touri = $page->url();
            } else {
                $touri = url($touri);
            }
            $code = intval(str_replace('_', '', \Kirby\Toolkit\A::get($redirects, 'code', $statusCode)));
            if (!$code || $code == 0) {
                $code = $statusCode;
            }

            // REDIRECT
            \Kirby\Http\Header::redirect($touri, $code);
            break;
        }
    }

    public static function codes()
    {
        if(option('debug')) {
            kirby()->cache('bnomei.redirects')->flush();
        }
        $codes = kirby()->cache('bnomei.redirects')->get('httpcodes');
        if (!$codes) {
            $codes =  [];
            foreach (\Kirby\Http\Header::$codes as $code => $label) {
                $codes[] = [
                    'code' => $code, // string: _302
                    'label' => $label,
                ];
            }
            kirby()->cache('bnomei.redirects')->set('httpcodes', $codes, 60*24);
        }
        return $codes;
    }
}
