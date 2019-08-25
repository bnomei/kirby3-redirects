<?php

declare(strict_types=1);

namespace Bnomei;

use Kirby\Http\Header;
use Kirby\Http\Url;
use Kirby\Toolkit\A;
use function option;

final class Redirects
{
    /*
     * @var array
     */
    private $options;

    public function __construct(array $options = [])
    {
        $defaults = [
            'code' => $this->normalizeCode(option('bnomei.redirects.code')),
            'querystring' => option('bnomei.redirects.querystring'),
            'map' => option('bnomei.redirects.map', []),
            'site.url' => site()->url(), // a) www.example.com or b) www.example.com/subfolder
            'request.uri' => $this->getRequestURI(),
        ];
        $this->options = array_merge($defaults, $options);

        foreach ($this->options as $key => $call) {
            if (is_callable($call)) {
                $this->options[$key] = $call();
            }
        }

        $this->checkForRedirect($this->options);
    }

    public function option(?string $key = null)
    {
        if ($key) {
            return A::get($this->options, $key);
        }
        return $this->options;
    }

    public function checkForRedirect(array $options): ?array
    {
        $map = A::get($options, 'map');
        if (! $map || count($map) === 0) {
            return null;
        }

        $siteurl = A::get($options, 'site.url');
        $requesturi = A::get($options, 'request.uri');

        foreach ($map as $redirect) {
            if ($this->matchesFromUri($redirect, $requesturi, $siteurl)) {
                return [
                    'uri' => $this->validateToUri($redirect),
                    'code' => $this->validateCode($redirect, A::get($options, 'code')),
                ];
            }
        }
        return null;
    }

    public function matchesFromUri(array $redirect, string $requesturi, string $siteurl): bool
    {
        $sitebase = Url::path($siteurl, true, true);
        $fromuri = A::get($redirect, 'fromuri');
        $fromuri = '/' . trim($sitebase . str_replace($siteurl, '', $fromuri), '/');
        return $fromuri === $requesturi;
    }

    private function getRequestURI(): string
    {
        $uri = array_key_exists("REQUEST_URI", $_SERVER) ? $_SERVER["REQUEST_URI"] : '/' . kirby()->request()->path();
        $uri = option('bnomei.redirects.querystring') ? $uri : strtok($uri, '?'); // / or /page or /subfolder or /subfolder/page
        return $uri;
    }

    private function normalizeCode($code): int
    {
        return intval(str_replace('_', '', $code));
    }

    private function validateToUri($redirect): string
    {
        $touri = '/' . trim(A::get($redirect, 'touri'), '/');
        $page = page($touri);
        if ($page) {
            $touri = $page->url();
        } else {
            $touri = url($touri);
        }
        return $touri;
    }

    private function validateCode(array $redirect, int $optionsCode): int
    {
        $redirectCode = $this->normalizeCode(A::get($redirect, 'code'));
        if (! $redirectCode || $redirectCode === 0) {
            $redirectCode = $optionsCode;
        }
        return $redirectCode;
    }

    public static function redirects($options = [])
    {
        $redirects = new self($options);
        $check = $redirects->checkForRedirect(
            $redirects->option()
        );
        if ($check && is_array($check)
            && array_key_exists('uri', $check)
            && array_key_exists('code', $check)
        ) {
            // @codeCoverageIgnoreStart
            Header::redirect($check['uri'], $check['code']);
            // @codeCoverageIgnoreEnd
        }
    }

    public static function codes(bool $force = false): ?array
    {
        $codes = null;
        if (! $force && ! option('debug')) {
            $codes = kirby()->cache('bnomei.redirects')->get('httpcodes');
        }
        if ($codes) {
            return $codes;
        }

        $codes = [];
        foreach (Header::$codes as $code => $label) {
            $codes[] = [
                'code' => $code, // string: _302
                'label' => $label,
            ];
        }
        kirby()->cache('bnomei.redirects')->set('httpcodes', $codes, 60 * 24 * 7);

        return $codes;
    }
}
