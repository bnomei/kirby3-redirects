<?php

declare(strict_types=1);

namespace Bnomei;

use Closure;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Content\Field;
use Kirby\Data\Yaml;
use Kirby\Http\Header;
use Kirby\Http\Url;
use Kirby\Toolkit\A;

use function option;

class Redirects
{
    private array $options;

    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'code' => option('bnomei.redirects.code'),
            'querystring' => option('bnomei.redirects.querystring'),
            'exact' => option('bnomei.redirects.exact'),
            'exact.code' => option('bnomei.redirects.exact.code') ?? option('bnomei.redirects.code'),
            'map' => option('bnomei.redirects.map'),
            'parent' => null, // will be set by loadRedirectsFromSource
            'shield.enabled' => option('bnomei.redirects.shield.enabled'),
            'shield.generic' => option('bnomei.redirects.shield.generic'),
            'shield.wordpress' => option('bnomei.redirects.shield.wordpress'),
            'shield.joomla' => option('bnomei.redirects.shield.joomla'),
            'shield.drupal' => option('bnomei.redirects.shield.drupal'),
            'shield.magento' => option('bnomei.redirects.shield.magento'),
            'shield.shopify' => option('bnomei.redirects.shield.shopify'),
            'site.url' => kirby()->url(), // a) www.example.com or b) www.example.com/subfolder, NOT site()->url() as that contains the language code
            'request.uri' => strval(A::get($options, 'request.uri', $this->getRequestURI())),
        ], $options);

        foreach ($this->options as $key => $call) {
            if ($call instanceof Closure && in_array($key, ['code', 'querystring', 'exact', 'exact.code', 'map'])) {
                $this->options[$key] = $call();
            }
        }

        // make sure the request.uri starts with a /
        $this->options['request.uri'] = '/'.ltrim($this->options['request.uri'], '/');

        $this->loadExactRedirectsFromSource($this->options['exact']);
        $this->loadRedirectsFromSource($this->options['map']);
        $this->addShieldToRedirects();
        $this->buildLookup();
        // keep map around to allow update/removes
        // $this->options['map'] = null; // NOPE!
    }

    public function option(?string $key = null): mixed
    {
        if ($key) {
            return A::get($this->options, $key);
        }

        return $this->options;
    }

    /**
     * @return array<string, string>
     */
    public function loadExactRedirectsFromSource(array|Field|null $source = null): array
    {
        if ($source instanceof Field) {
            // https://getkirby.com/docs/reference/templates/field-methods/yaml
            $source = $source->isNotEmpty() ? $source->yaml() : []; // @phpstan-ignore-line
        }

        $exact = [];
        if (is_array($source)) {
            foreach ($source as $fromuri => $touri) {
                if (! is_string($fromuri) || trim($fromuri) === '' || ! is_string($touri) || trim($touri) === '') {
                    continue;
                }

                $exact[$this->makeRelativePath($fromuri)] = trim($touri);
            }
        }

        $this->options['exact'] = $exact;

        return $exact;
    }

    public function loadRedirectsFromSource(array|Field|null $source = null): array
    {
        $this->options['parent'] = null;

        if ($source instanceof Field) {
            $this->options['parent'] = $source->parent();
            // https://getkirby.com/docs/reference/templates/field-methods/yaml
            $source = $source->isNotEmpty() ? $source->yaml() : []; // @phpstan-ignore-line
        }

        $this->options['redirects'] = is_array($source) ? $source : [];

        return $this->options['redirects'];
    }

    private function addShieldToRedirects(): array
    {
        if ($this->options['shield.enabled'] !== true) {
            return $this->options['redirects'];
        }
        $redirects = [];
        foreach ([
            'shield.generic',
            'shield.wordpress',
            'shield.joomla',
            'shield.drupal',
            'shield.magento',
            'shield.shopify',
        ] as $shield) {
            foreach ((array) $this->options[$shield] as $redirect) {
                $redirects[] = $redirect;
            }
        }

        $this->options['redirects'] = array_merge($redirects, $this->options['redirects']);

        return $this->options['redirects'];
    }

    private function buildLookup(): array
    {
        $this->options['lookup'] = [];
        foreach ($this->redirects() as $redirect) {
            if (! is_array($redirect) || ! array_key_exists('fromuri', $redirect)) {
                continue;
            }

            $fromuri = A::get($redirect, 'fromuri');
            if (! is_string($fromuri) || trim($fromuri) === '') {
                continue;
            }

            $this->options['lookup'][$this->makeRelativePath($fromuri)][] = $redirect;
        }

        return $this->options['lookup'];
    }

    public function redirects(): array
    {
        return (array) $this->options['redirects'];
    }

    /**
     * @return array<string, string>
     */
    public function exactRedirects(): array
    {
        $exact = $this->options['exact'];
        if (! is_array($exact)) {
            return [];
        }

        /** @var array<string, string> $exact */
        return $exact;
    }

    public function append(array $change): bool
    {
        // wrap single change in array of changes
        if (count($change) === count($change, COUNT_RECURSIVE)) {
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

        $data = array_merge($this->redirects(), $change);
        $this->options['redirects'] = $data;

        return $this->updateRedirects($data);
    }

    public function remove(array $change): bool
    {
        // wrap single change in array of changes
        if (count($change) === count($change, COUNT_RECURSIVE)) {
            $change = [$change];
        }

        $data = $this->redirects();
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

    public function sortAndUpdate(): bool
    {
        $r = $this->redirects();
        $r = A::sort($r, 'fromuri', 'asc');

        return $this->updateRedirects($r);
    }

    public function updateRedirects(array $data): bool
    {
        $parent = $this->getParent();
        if (! $parent) {
            return false;
        }

        // retrieve again for mutability
        if ($parent instanceof Site) {
            $parent = kirby()->site();
        } else {
            $parent = kirby()->page($parent->id());
        }

        if (! $parent) {
            return false;
        }

        return (bool) kirby()->impersonate('kirby', function () use ($parent, $data) {
            /** @var Field $map */
            $map = $this->option('map');
            $fieldKey = $map->key();
            // @codeCoverageIgnoreStart
            $parent->update([
                $fieldKey => Yaml::encode($data),
            ]);
            // @codeCoverageIgnoreEnd

            // static::flush(); // the hook will do this anyway
            return true;
        });
    }

    // getter function for parent value $option
    public function getParent(): Page|Site|null
    {
        return $this->options['parent'];
    }

    public static function isKnownValidRoute(string $path): bool
    {
        return kirby()->cache('bnomei.redirects')->get(md5($path)) !== null;
    }

    public static function flush(): bool
    {
        return kirby()->cache('bnomei.redirects')->flush();
    }

    public function checkForRedirect(?string $uri = null): ?Redirect
    {
        $requesturi = $uri ?? (string) $this->options['request.uri'];
        if ($redirect = $this->checkForExactRedirect($requesturi)) {
            return $redirect;
        }

        $map = $this->redirects();
        if (count($map) === 0) {
            return null;
        }

        $r = new Redirect;
        // try direct lookup first and only do that in a match
        if (array_key_exists($requesturi, $this->options['lookup'])) {
            $map = $this->options['lookup'][$requesturi];
        }
        foreach ($map as $redirect) {
            if (! array_key_exists('fromuri', $redirect) ||
                ! array_key_exists('touri', $redirect)
            ) {
                continue;
            }

            $r = $r->set(
                $this->makeRelativePath(A::get($redirect, 'fromuri', '')),
                A::get($redirect, 'touri', ''),
                A::get($redirect, 'code', $this->option('code'))
            );

            if ($r->matches($requesturi)) {
                return $r;
            }
        }

        return null;
    }

    private function checkForExactRedirect(string $requesturi): ?Redirect
    {
        $exact = $this->exactRedirects();
        if (! array_key_exists($requesturi, $exact)) {
            return null;
        }

        $code = $this->option('exact.code');
        if (! is_string($code) && ! is_int($code) && $code !== null) {
            $code = $this->option('code');
        }

        return new Redirect($requesturi, $exact[$requesturi], is_string($code) || is_int($code) ? $code : null);
    }

    private function makeRelativePath(string $url): string
    {
        $siteurl = A::get($this->options, 'site.url');
        $sitebase = Url::path($siteurl, true, true);
        $url = $siteurl !== '/' ? str_replace($siteurl, '', $url) : $url;

        return '/'.trim($sitebase.$url, '/');
    }

    private function getRequestURI(): string
    {
        $uri = array_key_exists('REQUEST_URI', $_SERVER) ? strval($_SERVER['REQUEST_URI']) : kirby()->request()->path()->toString(leadingSlash: true);
        $uri = option('bnomei.redirects.querystring') ? $uri : strtok($uri, '?'); // / or /page or /subfolder or /subfolder/page

        return $uri !== false ? $uri : '';
    }

    public function redirect(): void
    {
        $redirect = $this->checkForRedirect();
        if (! $redirect) {
            return;
        }

        $code = $redirect->code();
        kirby()->trigger('redirect:before', ['code' => $code, 'redirect' => $redirect]);

        // @codeCoverageIgnoreStart
        if ($code >= 300 && $code < 400) {
            Header::redirect(Redirect::url($redirect->to()), $code);
        } else {
            Header::status($code);
            exit();
        }
        // @codeCoverageIgnoreEnd
    }

    private static ?self $singleton = null;

    public static function singleton(array $options = []): self
    {
        // @codeCoverageIgnoreStart
        if (self::$singleton === null) {
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

    public static function staticCache(string $key, Closure $closure): mixed
    {
        if ($value = A::get(self::$cache, $key, null)) {
            return $value;
        }

        self::$cache[$key] = $closure();

        return self::$cache[$key];
    }
}
