# Kirby 3 Redirects

![Release](https://flat.badgen.net/packagist/v/bnomei/kirby3-redirects?color=ae81ff)
![Downloads](https://flat.badgen.net/packagist/dt/bnomei/kirby3-redirects?color=272822)
[![Build Status](https://flat.badgen.net/travis/bnomei/kirby3-redirects)](https://travis-ci.com/bnomei/kirby3-redirects)
[![Coverage Status](https://flat.badgen.net/coveralls/c/github/bnomei/kirby3-redirects)](https://coveralls.io/github/bnomei/kirby3-redirects) 
[![Maintainability](https://flat.badgen.net/codeclimate/maintainability/bnomei/kirby3-redirects)](https://codeclimate.com/github/bnomei/kirby3-redirects) 
[![Twitter](https://flat.badgen.net/badge/twitter/bnomei?color=66d9ef)](https://twitter.com/bnomei)


Setup performant [HTTP Status Code](https://en.wikipedia.org/wiki/List_of_HTTP_status_codes#3xx_Redirection) Redirects from within the Kirby Panel.

Kirby 3 Redirects can redirect any request URI to any response URI. It can also handle querystrings and regex.

## Similar Plugin

- [kirby-retour](https://github.com/distantnative/kirby-retour) but it can only handle Kirby Routes. It is the better choice when updating a Kirby 2 project or creating a brand new Kirby 3 project.

## Works well with

- [CSV Plugin](https://github.com/bnomei/kirby3-csv) to help you import and export data to the redirects structure.

## Commercial Usage

> <br>
> <b>Support open source!</b><br><br>
> This plugin is free but if you use it in a commercial project please consider to sponsor me or make a donation.<br>
> If my work helped you to make some cash it seems fair to me that I might get a little reward as well, right?<br><br>
> Be kind. Share a little. Thanks.<br><br>
> &dash; Bruno<br>
> &nbsp; 

| M | O | N | E | Y |
|---|----|---|---|---|
| [Github sponsor](https://github.com/sponsors/bnomei) | [Patreon](https://patreon.com/bnomei) | [Buy Me a Coffee](https://buymeacoff.ee/bnomei) | [Paypal dontation](https://www.paypal.me/bnomei/15) | [Hire me](mailto:b@bnomei.com?subject=Kirby) |

## Installation

- unzip [master.zip](https://github.com/bnomei/kirby3-redirects/archive/master.zip) as folder `site/plugins/kirby3-redirects` or
- `git submodule add https://github.com/bnomei/kirby3-redirects.git site/plugins/kirby3-redirects` or
- `composer require bnomei/kirby3-redirects`

## Setup

Add the `plugin-redirects` section to your `site.yml` and add redirects in the panel.

**site.yml**
```yaml
sections:
  # ...other sections
  redirects:
    extends: plugin-redirects3xx
```

> If you need all http codes you can use `extends: plugin-redirects` instead.

## Usage

In the structure field or using the provided site methods add Request-URIs `fromuri` like 

- `projects/cool`
- `projects?id=123`
- `projects/cool.html`
- `projects\/.*\.html`
- `blog\/(?P<year>\d{4})_(?P<slug>.*)\.html`

and set Response-URIs `touri` like 

- `projects/changed-slug`
- `https://exter.nal`
- `blog/$year/$slug`

as well as a HTTP Status Code `code` like `301` or `302`.

This makes it the ideal choice when porting a non Kirby project.

## Site Methods

The site methods `appendRedirects` and `removeRedirects` allow you to programmatically change the redirects table (if stored in a Page/Site-Object).

```php
// add single item
$success = site()->appendRedirects(
    ['fromuri'=>'/posts?id=1', 'touri'=>'/blog/1', 'code'=>301]
);

// add multiple items with nested array
$success = site()->appendRedirects([
    ['fromuri'=>'/posts?id=2', 'touri'=>'/blog/2', 'code'=>301],
    // ...
    ['fromuri'=>'/posts?id=999', 'touri'=>'/blog/999', 'code'=>301],
]);

// remove single item
$success = site()->removeRedirects(
    ['fromuri'=>'/posts?id=1', 'touri'=>'/blog/1']
);

// remove multiple items with nested array
$success = site()->removeRedirects([
    ['fromuri'=>'/posts?id=3', 'touri'=>'/blog/3'],
    ['fromuri'=>'/posts?id=5', 'touri'=>'/blog/5'],
    ['fromuri'=>'/posts?id=7', 'touri'=>'/blog/7'],
]);
```

## Protecting your Kirby from Bots

This plugin will block various routes of other popular CMS. It is enabled by default and will reduce the load on your website caused by bots looking for vulnerabilities in other CMS.

- Wordpress
- Joomla
- Drupal
- Magento
- Shopify

## Settings

| bnomei.redirects. | Default    | Description                                                                                                                                                            |            
|-------------------|------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| code              | `301`      |                                                                                                                                                                        |
| querystring       | `true`     | do keep querystring in request URI. example: `https://kirby3-plugins.bnomei.com/projects?id=12` => `projects?id=12`                                                    |
| only-empty-results       | `false`    | only redirect if the result is empty in the router                                                                                                                     |
| map               | `callback` | A closure to get the structure from `content/site.txt`. Define you own if you want the section to be in a different blueprint or skip the blueprint and just use code. |
| block.enabled     | `true`     | Block various routes of other popular CMS                                                                                                                              |

## Disclaimer

This plugin is provided "as is" with no guarantee. Use it at your own risk and always test it yourself before using it in a production environment. If you find any issues, please [create a new issue](https://github.com/bnomei/kirby3-redirects/issues/new).

## License

[MIT](https://opensource.org/licenses/MIT)

It is discouraged to use this plugin in any project that promotes racism, sexism, homophobia, animal abuse, violence or any other form of hate speech.

## Credits

This plugins is similar yet way less powerful than K2 version of

- https://github.com/ivinteractive/kirbycms-redirects
