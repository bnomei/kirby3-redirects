# Kirby 3 Redirects

![GitHub release](https://img.shields.io/github/release/bnomei/kirby3-redirects.svg?maxAge=1800) ![License](https://img.shields.io/github/license/mashape/apistatus.svg) ![Kirby Version](https://img.shields.io/badge/Kirby-3%2B-black.svg)

Setup [HTTP Status Code](https://en.wikipedia.org/wiki/List_of_HTTP_status_codes#3xx_Redirection)  Redirects from within the Kirby Panel.

## Commerical Usage

This plugin is free but if you use it in a commercial project please consider to 
- [make a donation 🍻](https://www.paypal.me/bnomei/0.5) or
- [buy me ☕](https://buymeacoff.ee/bnomei) or
- [buy a Kirby license using this affiliate link](https://a.paddle.com/v2/click/1129/35731?link=1170)

## Setup

Add the `plugin-redirects` section to your `site.yml` and add redirects in the panel.

**site.yml**
```yaml
sections:
  # ...other sections
  redirects:
    extends: plugin-redirects
```

Since v1.1.0 the plugin will register itself with a `route:before`-hook and take care of the redirecting automatically. Many thanks to _Sebastian Aschenbach_ for suggesting this solution.

## Settings

All settings require `bnomei.redirects.` as prefix.

**code**
- default: `301`

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
