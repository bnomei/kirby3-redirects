# Kirby 3 Redirects

![GitHub release](https://img.shields.io/github/release/bnomei/kirby3-redirects.svg?maxAge=1800) ![License](https://img.shields.io/github/license/mashape/apistatus.svg) ![Kirby Version](https://img.shields.io/badge/Kirby-3-black.svg) ![Kirby 3 Pluginkit](https://img.shields.io/badge/Pluginkit-YES-cca000.svg) [![Build Status](https://travis-ci.com/bnomei/kirby3-redirects.svg?branch=master)](https://travis-ci.com/bnomei/kirby3-redirects) [![Coverage Status](https://coveralls.io/repos/github/bnomei/kirby3-redirects/badge.svg?branch=master)](https://coveralls.io/github/bnomei/kirby3-redirects?branch=master) [![Gitter](https://badges.gitter.im/bnomei-kirby-3-plugins/community.svg)](https://gitter.im/bnomei-kirby-3-plugins/community?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)

Setup [HTTP Status Code](https://en.wikipedia.org/wiki/List_of_HTTP_status_codes#3xx_Redirection)  Redirects from within the Kirby Panel.

Kirby 3 Redirects can handle Request-URIs like `projects?id=123`, `project/cool.html` and send Response-URIs like `https://exter.nal`. This makes it the ideal choice when porting a non Kirby project.

## Similar Plugin

- [kirby-retour](https://github.com/distantnative/kirby-retour) but it can only handle Kirby Routes. It is the better choice when updating a Kirby 2 project or creating a brand new Kirby 3 project.

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

## Settings

All settings require `bnomei.redirects.` as prefix.

**code**
- default: `301`

**querystring**
- default: `true` do keep querystring in request URI
- example: `https://kirby3-plugins.bnomei.com/projects?id=12` => `projects?id=12`

**map**
- default: A closure to get the structure from `site.txt`. Define you own if you want the section to be in a different blueprint or skip the blueprint and just use code.

## Disclaimer

This plugin is provided "as is" with no guarantee. Use it at your own risk and always test it yourself before using it in a production environment. If you find any issues, please [create a new issue](https://github.com/bnomei/kirby3-redirects/issues/new).

## License

[MIT](https://opensource.org/licenses/MIT)

It is discouraged to use this plugin in any project that promotes racism, sexism, homophobia, animal abuse, violence or any other form of hate speech.

## Credits

This plugins is similar yet way less powerful than K2 version of

- https://github.com/ivinteractive/kirbycms-redirects
