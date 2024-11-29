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
            'map' => option('bnomei.redirects.map'),
            'parent' => null, // will be set by loadRedirectsFromSource
            'shield.enabled' => option('bnomei.redirects.shield.enabled'),
            'shield.wordpress' => option('bnomei.redirects.shield.wordpress'),
            'shield.joomla' => option('bnomei.redirects.shield.joomla'),
            'shield.drupal' => option('bnomei.redirects.shield.drupal'),
            'shield.magento' => option('bnomei.redirects.shield.magento'),
            'shield.shopify' => option('bnomei.redirects.shield.shopify'),
            'site.url' => kirby()->url(), // a) www.example.com or b) www.example.com/subfolder, NOT site()->url() as that contains the language code
            'request.uri' => strval(A::get($options, 'request.uri', $this->getRequestURI())),
        ], $options);

        foreach ($this->options as $key => $call) {
            if ($call instanceof Closure && in_array($key, ['code', 'querystring', 'map'])) {
                $this->options[$key] = $call();
            }
        }

        // make sure the request.uri starts with a /
        $this->options['request.uri'] = '/'.ltrim($this->options['request.uri'], '/');

        $this->loadRedirectsFromSource($this->options['map']);
        $this->addShieldToRedirects();
        // keep map around to allow update/removes
        //$this->options['map'] = null; // NOPE!
    }

    public function option(?string $key = null): mixed
    {
        if ($key) {
            return A::get($this->options, $key);
        }

        return $this->options;
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

    private function addShieldToRedirects(): bool
    {
        if ($this->options['shield.enabled'] !== true) {
            return false;
        }

        $this->options['redirects'] = array_merge(
            $this->options['shield.wordpress'],
            $this->options['shield.joomla'],
            $this->options['shield.drupal'],
            $this->options['shield.magento'],
            $this->options['shield.shopify'],
            $this->options['redirects']
        );

        return true;
    }

    public function redirects(): array
    {
        return (array) $this->options['redirects'];
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

    public function updateRedirects(array $data): bool
    {
        $parent = $this->getParent();
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

    public function checkForRedirect(): ?Redirect
    {
        $requesturi = (string) $this->options['request.uri'];
        if (static::isKnownValidRoute($requesturi)) {
            return null;
        }

        $map = $this->redirects();
        if (count($map) === 0) {
            return null;
        }

        foreach ($map as $redirect) {
            if (! array_key_exists('fromuri', $redirect) ||
                ! array_key_exists('touri', $redirect)
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
        // so it is not checked again until the cache is flushed
        kirby()->cache('bnomei.redirects')->set(md5($requesturi), [
            $requesturi,
        ]);

        return null;
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
