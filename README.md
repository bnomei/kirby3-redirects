# Kirby Redirects

[![Kirby 5](https://flat.badgen.net/badge/Kirby/5?color=ECC748)](https://getkirby.com)
![PHP 8.2](https://flat.badgen.net/badge/PHP/8.2?color=4E5B93&icon=php&label)
![Release](https://flat.badgen.net/packagist/v/bnomei/kirby3-redirects?color=ae81ff&icon=github&label)
![Downloads](https://flat.badgen.net/packagist/dt/bnomei/kirby3-redirects?color=272822&icon=github&label)
[![Coverage](https://flat.badgen.net/codeclimate/coverage/bnomei/kirby3-redirects?icon=codeclimate&label)](https://codeclimate.com/github/bnomei/kirby3-redirects)
[![Maintainability](https://flat.badgen.net/codeclimate/maintainability/bnomei/kirby3-redirects?icon=codeclimate&label)](https://codeclimate.com/github/bnomei/kirby3-redirects/issues)
[![Discord](https://flat.badgen.net/badge/discord/bnomei?color=7289da&icon=discord&label)](https://discordapp.com/users/bnomei)
[![Buymecoffee](https://flat.badgen.net/badge/icon/donate?icon=buymeacoffee&color=FF813F&label)](https://www.buymeacoffee.com/bnomei)

## Features

- ✏️ Define [HTTP Status Code](https://en.wikipedia.org/wiki/List_of_HTTP_status_codes#3xx_Redirection) redirects from within the **Kirby Panel**.
- 🔀 Setup redirects from any request URI to any response URI, not just Kirby routes.
- 🕵️ Match on query-strings like `?foo=bar` and forward data with regex `(?P<year>\d{4})`.
- 🛡️ Protects your website from attacks by blocking 50+ routes/patterns of other popular CMS.
- 🪝 With the hooks you can add custom logic like logging 404s.
- 🏎️ Highly performant due to caching on repeated valid requests.

## Installation

- unzip [master.zip](https://github.com/bnomei/kirby3-redirects/archive/master.zip) as folder
  `site/plugins/kirby3-redirects` or
- `git submodule add https://github.com/bnomei/kirby3-redirects.git site/plugins/kirby3-redirects` or
- `composer require bnomei/kirby3-redirects`

## Setup: add Redirects section to Site blueprint

Add the `plugin-redirects` section to your `site.yml`. This will allow you to create redirects in via the Panel or
programmatically.

**site/blueprints/site.yml**

```yaml
sections:
    # ...other sections
    redirects:
        extends: plugin-redirects3xx
```

> [!TIP]
> If you want to able to set all HTTP Status code from within the panel, not just the 3xx range, you can use
`extends: plugin-redirects` instead.

> [!TIP]
> Instead of using the site blueprint you can also use the `redirects` section in any pages blueprint as long as you
> adjust the `bnomei.redirects.map` option accordingly so the plugin knows where to find the redirects.

## Usage

In the Structure Field within the Panel add a Request-URIs `fromuri`, set a Response-URIs `touri` and a HTTP Status Code `code` like `301` or `302`.

| fromuri                                    | to                      | code  |
|--------------------------------------------|-------------------------|-------|
| `projects/cool`                            | `projects/changed-slug` | `301` |
| `projects/cool.html`                       | `projects/changed-slug` | `301` |
| `projects\/.*\.html`                       | `projects/changed-slug` | `301` |
| `some/broken-link`                         | `https://exter.nal`     | `301` |
| `blog\/(?P<year>\d{4})_(?P<slug>.*)\.html` | `blog/$year/$slug`      | `301` |

## Shielding your website from attacks

This plugin will **block 50+ routes/patterns** of other popular CMS. It is enabled by default and will reduce the load
on your website caused by bots/attackers looking for vulnerabilities found in other CMS.

- Drupal
- Joomla
- Magento
- Shopify
- Wordpress

You can track any redirects, including the blocked requests from the *shield*, using the `redirect:before` and
`404:before` hooks.

## Hooks

This plugin will trigger the following hooks, which you could use to build your own tracking or logging.

- `redirect:before($code, $redirect)`
- `404:before($route, $path, $method)`

**site/config/config.php**

```php
<?php 
return [
    'hooks' => [
        'redirect:before' => function (int $code, \Bnomei\Redirect $redirect) {
            // do whatever you need, like...
            monolog('redirect')->info($code, [
                'from' => $redirect->from(), 
                'to' => $redirect->to()
            ]);
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
    // other config...
];
```

## Programmatically changing the redirects table

The site methods `appendRedirects` and `removeRedirects` allow you to programmatically change the redirects table (if
stored in a Page/Site-Object Field, see `map` config option).

```php
// add single item
$success = site()->appendRedirects(
    ['fromuri' => '/posts?id=1', 'touri' => '/blog/1', 'code' => 301]
);

// add multiple items with nested array
$success = site()->appendRedirects([
    ['fromuri' => '/posts?id=2', 'touri' => '/blog/2', 'code' => 301],
    // ...
    ['fromuri' => '/posts?id=999', 'touri' => '/blog/999', 'code' => 301],
]);

// remove single item
$success = site()->removeRedirects(
    ['fromuri' => '/posts?id=1', 'touri' => '/blog/1']
);

// remove multiple items with nested array
$success = site()->removeRedirects([
    ['fromuri' => '/posts?id=3', 'touri' => '/blog/3'],
    ['fromuri' => '/posts?id=5', 'touri' => '/blog/5'],
    ['fromuri' => '/posts?id=7', 'touri' => '/blog/7'],
]);
```

## Cache & Performance

The plugin will cache any valid URI request and thus vastly improve performance on repeated requests to that URI. Thus
the plugin will not check for redirects at all if it know that the URI will lead to a valid content page in Kirby. When
the redirects table is changed or any content is updated via the Panel the cache will be cleared.

For best performance, set either
the [global or plugin-specific cache driver](https://getkirby.com/docs/reference/system/options/cache) to one using the
server's memory, not the default using files on the hard disk (even on SSDs). If available, I suggest Redis/APCu or
leave it at `file` otherwise.

**site/config/config.php**

```php
return [
  'cache' => [
    'driver' => 'apcu', // or redis
  ],
  'bnomei.redirects.cache' => [
    'type' => 'apcu', // or redis
  ],
];
```

## Similar Plugins

- [kirby-retour](https://github.com/distantnative/kirby-retour) while featuring a nice UI and built-in 404 tracking it
  can only handle Kirby routes (with pattern matching) but not any request URI.

## Settings

| bnomei.redirects.  | Default    | Description                                                                                                                                                            |            
|--------------------|------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| code               | `301`      |                                                                                                                                                                        |
| querystring        | `true`     | do keep querystring in request URI. example: `https://kirby3-plugins.bnomei.com/projects?id=12` => `projects?id=12`                                                    |
| only-empty-results | `false`    | only redirect if the result is empty in the router                                                                                                                     |
| map                | `callback` | A closure to get the structure from `content/site.txt`. Define you own if you want the section to be in a different blueprint or skip the blueprint and just use code. |
| shield.enabled     | `true`     | Block various routes of other popular CMS                                                                                                                              |

## Disclaimer

This plugin is provided "as is" with no guarantee. Use it at your own risk and always test it yourself before using it
in a production environment. If you find any issues,
please [create a new issue](https://github.com/bnomei/kirby3-redirects/issues/new).

## License

[MIT](https://opensource.org/licenses/MIT)

It is discouraged to use this plugin in any project that promotes racism, sexism, homophobia, animal abuse, violence or
any other form of hate speech.
