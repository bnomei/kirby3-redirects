# Kirby 3 Redirects

![Release](https://flat.badgen.net/packagist/v/bnomei/kirby3-redirects?color=ae81ff)
![Stars](https://flat.badgen.net/packagist/ghs/bnomei/kirby3-redirects?color=272822)
![Downloads](https://flat.badgen.net/packagist/dt/bnomei/kirby3-redirects?color=272822)
![Issues](https://flat.badgen.net/packagist/ghi/bnomei/kirby3-redirects?color=e6db74)
[![Build Status](https://flat.badgen.net/travis/bnomei/kirby3-redirects)](https://travis-ci.com/bnomei/kirby3-redirects)
[![Coverage Status](https://flat.badgen.net/coveralls/c/github/bnomei/kirby3-redirects)](https://coveralls.io/github/bnomei/kirby3-redirects) 
[![Maintainability](https://flat.badgen.net/codeclimate/maintainability/bnomei/kirby3-redirects)](https://codeclimate.com/github/bnomei/kirby3-redirects) 
[![Demo](https://flat.badgen.net/badge/website/examples?color=f92672)](https://kirby3-plugins.bnomei.com/autoid) 
[![Gitter](https://flat.badgen.net/badge/gitter/chat?color=982ab3)](https://gitter.im/bnomei-kirby-3-plugins/community) 
[![Twitter](https://flat.badgen.net/badge/twitter/bnomei?color=66d9ef)](https://twitter.com/bnomei)


Setup [HTTP Status Code](https://en.wikipedia.org/wiki/List_of_HTTP_status_codes#3xx_Redirection)  Redirects from within the Kirby Panel.

Kirby 3 Redirects can handle Request-URIs like `projects?id=123`, `project/cool.html` and send Response-URIs like `https://exter.nal`. This makes it the ideal choice when porting a non Kirby project.

## Similar Plugin

- [kirby-retour](https://github.com/distantnative/kirby-retour) but it can only handle Kirby Routes. It is the better choice when updating a Kirby 2 project or creating a brand new Kirby 3 project.

## Works well with

- [CSV Plugin](https://github.com/bnomei/kirby3-csv) to help you import and export data to the redirects structure.

## Commerical Usage

This plugin is free but if you use it in a commercial project please consider to 
- [make a donation 🍻](https://www.paypal.me/bnomei/2) or
- [buy me ☕](https://buymeacoff.ee/bnomei) or
- [buy a Kirby license using this affiliate link](https://a.paddle.com/v2/click/1129/35731?link=1170)

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

> If you need all http codes you can use `extends: plugin-redirects` instead which calls the api to retrieve them (once for each redirect). This is not advised if you have a lot of redirects.

> Since v1.1.0 the plugin will register itself with a `route:before`-hook and take care of the redirecting automatically. Many thanks to _Sebastian Aschenbach_ for suggesting this solution.

## Site Methods

The site methods `appendRedirect` and `removeRedirect` allow you to programmatically change the redirects table (if stored in a Page/Site-Object).

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

## Settings

| bnomei.redirects.         | Default        | Description               |            
|---------------------------|----------------|---------------------------|
| code | `301` | |
| querystring | `true` | do keep querystring in request URI. example: `https://kirby3-plugins.bnomei.com/projects?id=12` => `projects?id=12` |
| map | `callback` | A closure to get the structure from `content/site.txt`. Define you own if you want the section to be in a different blueprint or skip the blueprint and just use code. |

## Disclaimer

This plugin is provided "as is" with no guarantee. Use it at your own risk and always test it yourself before using it in a production environment. If you find any issues, please [create a new issue](https://github.com/bnomei/kirby3-redirects/issues/new).

## License

[MIT](https://opensource.org/licenses/MIT)

It is discouraged to use this plugin in any project that promotes racism, sexism, homophobia, animal abuse, violence or any other form of hate speech.

## Credits

This plugins is similar yet way less powerful than K2 version of

- https://github.com/ivinteractive/kirbycms-redirects
