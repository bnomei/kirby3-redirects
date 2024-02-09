<?php

declare(strict_types=1);

namespace Bnomei;

use Closure;
use Exception;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Content\Field;
use Kirby\Data\Yaml;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;
use Kirby\Http\Header;
use Kirby\Http\Url;
use Kirby\Toolkit\A;

use Kirby\Toolkit\Str;

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
            'block.enabled' => option('bnomei.redirects.block.enabled'),
            'block.wordpress' => option('bnomei.redirects.block.wordpress'),
            'block.joomla' => option('bnomei.redirects.block.joomla'),
            'block.drupal' => option('bnomei.redirects.block.drupal'),
            'block.magento' => option('bnomei.redirects.block.magento'),
            'block.shopify' => option('bnomei.redirects.block.shopify'),
            'site.url' => site()->url(), // a) www.example.com or b) www.example.com/subfolder
            'request.uri' => A::get($options, 'request.uri', $this->getRequestURI()),
        ];
        $this->options = array_merge($defaults, $options);

        foreach ($this->options as $key => $call) {
            if (is_callable($call) && in_array($key, ['code', 'querystring', 'map'])) {
                $this->options[$key] = $call();
            }
        }

        // make sure the request.uri starts with a /
        $this->options['request.uri'] = '/' . ltrim($this->options['request.uri'], '/');

        $this->options['parent'] = is_object($this->options['map']) ? $this->options['map']->parent() : null;

        $this->options['redirects'] = $this->map($this->options['map']);
        //$this->options['map'] = null; // free memory
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
                    $this->flush();
                    return true;
                    // @codeCoverageIgnoreStart
                } catch (Exception $ex) {
                }
                // @codeCoverageIgnoreEnd
            }
        }
        return false;
    }

    // getter function for parent value $option
    public function getParent(): Page|Site|null
    {
        return $this->option('parent');
    }

    public function validRoutesDir(): string
    {
        $dir = kirby()->cache('bnomei.redirects')->root() . '/validroutes';
        if (!Dir::exists($dir)) {
            Dir::make($dir);
        }
        return $dir;
    }

    public function isKnownValidRoute(string $path): bool
    {
        return F::exists($this->validRoutesDir() . '/' . md5($path));
    }

    public function flush(): bool
    {
        return Dir::remove($this->validRoutesDir());
    }

    public function checkForRedirect(): ?Redirect
    {
        $map = $this->option('redirects');

        // add block to map
        if ($this->options['block.enabled']) {
            $map = array_merge(
                $this->options['block.wordpress'],
                $this->options['block.joomla'],
                $this->options['block.drupal'],
                $this->options['block.magento'],
                $this->options['block.shopify'],
                $map ?? []
            );
        }

        if (! $map || count($map) === 0) {
            return null;
        }

        $requesturi = (string) $this->option('request.uri');

        if ($this->isKnownValidRoute($requesturi)) {
            return null;
        }


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

        // no redirect found, flag as valid route
        F::write($this->validRoutesDir() . '/' . md5($requesturi), '');

        return null;
    }

    private function makeRelativePath(string $url)
    {
        $siteurl = A::get($this->options, 'site.url');
        $sitebase = Url::path($siteurl, true, true);
        $url = $siteurl !== '/' ? str_replace($siteurl, '', $url) : $url;

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
            $code = $check->code();
            if ($code >= 300 && $code < 400) {
                Header::redirect(Redirect::url($check->to()), $code);
            } else {
                Header::status($code);
                die();
            }

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
        // NOTE: do not use a cache in this method as it is
        // called in the panel php blueprint and the cache
        // is not available there yet. => NullCache issue

        // $cache = kirby()->cache('bnomei.redirects');
        // $codes = null;
        // if (! $force && ! option('debug')) {
        //     $codes = $cache->get('httpcodes');
        // }
        // if ($codes) {
        //     return $codes;
        // }

        $codes = [];
        foreach (Header::$codes as $code => $label) {
            $codes[] = [
                'code' => $code, // string: _302
                'label' => $label,
            ];
        }
        // $cache->set('httpcodes', $codes, 60 * 24 * 7);

        return $codes;
    }

    public static array $cache = [];

    public static function staticCache(string $key, Closure $closure)
    {
        if ($value = A::get(static::$cache, $key, null)) {
            return $value;
        }

        if (!is_string($closure) && is_callable($closure)) {
            static::$cache[$key] = $closure();
        }

        return static::$cache[$key];
    }
}
