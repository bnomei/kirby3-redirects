<?php

declare(strict_types=1);

namespace Bnomei;

use Kirby\Cms\Field;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Data\Yaml;
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
            'code' => option('bnomei.redirects.code'),
            'querystring' => option('bnomei.redirects.querystring'),
            'map' => option('bnomei.redirects.map'),
            'site.url' => site()->url(), // a) www.example.com or b) www.example.com/subfolder
            'request.uri' => $this->getRequestURI(),
        ];
        $this->options = array_merge($defaults, $options);

        foreach ($this->options as $key => $call) {
            if (is_callable($call) && in_array($key, ['code', 'querystring', 'map'])) {
                $this->options[$key] = $call();
            }
        }
        $this->options['redirects'] = $this->map($this->options['map']);

        $this->checkForRedirect($this->options);
    }

    public function option(?string $key = null)
    {
        if ($key) {
            return A::get($this->options, $key);
        }
        return $this->options;
    }

    public function map($redirects = null)
    {
        if (is_a($redirects, Field::class)) {
            return $redirects->isNotEmpty() ? $redirects->yaml() : [];
        }
        return is_array($redirects) ? $redirects : [];
    }

    public function append(array $change): bool
    {
        if (is_array($change) &&
            count($change) === count($change, COUNT_RECURSIVE)
        ) {
            $change = [$change];
        }

        $code = $this->option('code');
        $change = array_map(function ($v) use ($code) {
            $redirect = new Redirect(
                A::get($v, 'fromuri'),
                A::get($v, 'touri'),
                A::get($v, 'code', $code)
            );
            return $redirect->toArray();
        }, $change);

        $data = array_merge(
            $this->option('redirects'),
            $change
        );
        $this->options['redirects'] = $data;
        return $this->updateRedirects($data);
    }

    public function remove(array $change): bool
    {
        if (is_array($change) &&
            count($change) === count($change, COUNT_RECURSIVE)
        ) {
            $change = [$change];
        }

        $data = $this->option('redirects');
        $copy = $data;
        foreach ($change as $item) {
            foreach ($copy as $key => $redirect) {
                if (A::get($redirect, 'fromuri') === A::get($item, 'fromuri') &&
                    A::get($redirect, 'touri') === A::get($item, 'touri')) {
                    unset($data[$key]);
                    break; // exit inner loop
                }
            }
        }
        $this->options['redirects'] = $data;
        return $this->updateRedirects($data);
    }

    public function updateRedirects(array $data): bool
    {
        $map = $this->option('map');
        if (is_a($map, Field::class)) {
            $page = $map->parent();
            if (is_a($page, Site::class) ||
                is_a($page, Page::class)
            ) {
                try {
                    kirby()->impersonate('kirby');
                    $page->update([
                        $map->key() => Yaml::encode($data),
                    ]);
                    return true;
                    // @codeCoverageIgnoreStart
                } catch (\Exception $ex) {
                }
                // @codeCoverageIgnoreEnd
            }
        }
        return false;
    }

    public function checkForRedirect(): ?Redirect
    {
        $map = $this->option('redirects');
        if (! $map || count($map) === 0) {
            return null;
        }

        $requesturi = (string) $this->option('request.uri');

        foreach ($map as $redirect) {
            if (!array_key_exists('fromuri', $redirect) ||
                !array_key_exists('touri', $redirect)
            ) {
                continue;
            }
            $redirect = new Redirect(
                $this->makeRelativePath(A::get($redirect, 'fromuri', '')),
                A::get($redirect, 'touri', ''),
                A::get($redirect, 'code', $this->option('code'))
            );

            if ($redirect->matches($requesturi)) {
                return $redirect;
            }
        }
        return null;
    }

    private function makeRelativePath(string $url)
    {
        $siteurl = A::get($this->options, 'site.url');
        $sitebase = Url::path($siteurl, true, true);
        $url = str_replace($siteurl, '', $url);

        return '/' . trim($sitebase . $url, '/');
    }

    private function getRequestURI(): string
    {
        $uri = array_key_exists("REQUEST_URI", $_SERVER) ? $_SERVER["REQUEST_URI"] : '/' . kirby()->request()->path();
        $uri = option('bnomei.redirects.querystring') ? $uri : strtok($uri, '?'); // / or /page or /subfolder or /subfolder/page

        return $uri;
    }

    public function redirect()
    {
        $check = $this->checkForRedirect();

        if ($check) {
            // @codeCoverageIgnoreStart
            Header::redirect(Redirect::url($check->to()), $check->code());
            // @codeCoverageIgnoreEnd
        }
    }

    private static $singleton;

    public static function singleton($options = []): Redirects
    {
        // @codeCoverageIgnoreStart
        if (! self::$singleton) {
            self::$singleton = new self($options);
        }
        // @codeCoverageIgnoreEnd

        return self::$singleton;
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
