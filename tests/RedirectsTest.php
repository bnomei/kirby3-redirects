<?php

require_once __DIR__.'/../vendor/autoload.php';

use Bnomei\Redirects;

test('construct', function () {
    $redirects = new Redirects;
    expect($redirects)->toBeInstanceOf(Redirects::class);
});
test('singleton', function () {
    $redirects = Redirects::singleton();
    expect($redirects)->toBeInstanceOf(Redirects::class);
});
test('does not redirect other page', function () {
    $options = [
        'site.url' => 'http://redirects.test/',
        'request.uri' => '/projects/ahmic',
    ];
    $redirects = new Redirects($options);

    expect($redirects->option())->toBeArray();
    expect($redirects->option('site.url'))->toEqual('http://redirects.test/');
    expect($redirects->option('does not exist'))->toBeNull();

    $check = $redirects->checkForRedirect();
    expect($check)->toBeNull();
});
test('redirect page', function () {
    $options = [
        'site.url' => 'http://redirects.test/',
        'request.uri' => '/building/ahmic',
    ];
    $redirects = new Redirects($options);
    $check = $redirects->checkForRedirect();
    expect($check->code() === 301)->toBeTrue();
    expect($check->to())->toEqual('projects/ahmic/');
});
test('duplicate redirects keep first match', function () {
    $options = [
        'site.url' => 'http://redirects.test/',
        'request.uri' => '/same',
        'map' => [
            ['fromuri' => '/same', 'touri' => '/first', 'code' => 301],
            ['fromuri' => '/same', 'touri' => '/second', 'code' => 302],
        ],
        'shield.enabled' => false,
    ];
    $redirects = new Redirects($options);
    $check = $redirects->checkForRedirect();

    expect($check)->not()->toBeNull();
    expect($check->to())->toEqual('/first');
    expect($check->code())->toEqual(301);
});
test('redirect extension', function () {
    $options = [
        'site.url' => 'http://redirects.test/',
        'request.uri' => '/building/ahmic.html',
    ];
    $redirects = new Redirects($options);
    $check = $redirects->checkForRedirect();
    expect($check->code() === 302)->toBeTrue();
});
test('redirect query', function () {
    $options = [
        'site.url' => 'http://redirects.test/',
        'request.uri' => '/projects?id=1',
    ];
    $redirects = new Redirects($options);
    $check = $redirects->checkForRedirect();
    expect($check->code() === 303)->toBeTrue();
});
test('redirect external', function () {
    $options = [
        'site.url' => 'http://redirects.test/',
        'request.uri' => '/projects/external',
    ];
    $redirects = new Redirects($options);
    $check = $redirects->checkForRedirect();
    expect($check->code() === 301)->toBeTrue();
});
test('static codes', function () {
    $codes = Redirects::codes();
    expect($codes)->toBeArray();
    expect($codes)->toHaveCount(25);
});
test('static codes forced', function () {
    $codes = Redirects::codes(true);
    expect($codes)->toBeArray();
    expect($codes)->toHaveCount(25);
});
test('no map', function () {
    $options = [
        'site.url' => 'http://redirects.test/',
        'request.uri' => '/projects/ahmic',
        'map' => null,
    ];
    $redirects = new Redirects($options);
    $check = $redirects->checkForRedirect();
    expect($check)->toBeNull();
});
test('exact redirect', function () {
    $options = [
        'site.url' => 'http://redirects.test/',
        'request.uri' => '/legacy/exact.htm',
        'exact' => [
            '/legacy/exact.htm' => '/projects/ahmic',
        ],
        'map' => null,
        'shield.enabled' => false,
    ];
    $redirects = new Redirects($options);
    $check = $redirects->checkForRedirect();

    expect($check)->not()->toBeNull();
    expect($check->code())->toEqual(301);
    expect($check->from())->toEqual('/legacy/exact.htm');
    expect($check->to())->toEqual('/projects/ahmic');
});
test('exact redirect code', function () {
    $options = [
        'site.url' => 'http://redirects.test/',
        'request.uri' => '/legacy/exact-code.htm',
        'exact' => [
            '/legacy/exact-code.htm' => '/projects/ahmic',
        ],
        'exact.code' => 308,
        'map' => null,
        'shield.enabled' => false,
    ];
    $redirects = new Redirects($options);
    $check = $redirects->checkForRedirect();

    expect($check)->not()->toBeNull();
    expect($check->code())->toEqual(308);
});
test('exact redirect source closure', function () {
    $options = [
        'site.url' => 'http://redirects.test/',
        'request.uri' => '/legacy/exact-closure.htm',
        'exact' => fn () => [
            '/legacy/exact-closure.htm' => '/projects/ahmic',
        ],
        'map' => null,
        'shield.enabled' => false,
    ];
    $redirects = new Redirects($options);
    $check = $redirects->checkForRedirect();

    expect($check)->not()->toBeNull();
    expect($check->to())->toEqual('/projects/ahmic');
});
test('exact redirects do not use regex matching', function () {
    $options = [
        'site.url' => 'http://redirects.test/',
        'request.uri' => '/legacy/regex-test.htm',
        'exact' => [
            '/legacy/.*' => '/projects/ahmic',
        ],
        'map' => null,
        'shield.enabled' => false,
    ];
    $redirects = new Redirects($options);
    $check = $redirects->checkForRedirect();

    expect($check)->toBeNull();
});
test('exact redirect ignores stale redirect cache entries', function () {
    Redirects::flush();
    kirby()->cache('bnomei.redirects')->set(md5('/legacy/cached-exact.htm'), [
        '/legacy/cached-exact.htm',
    ]);

    $redirects = new Redirects([
        'site.url' => 'http://redirects.test/',
        'request.uri' => '/legacy/cached-exact.htm',
        'exact' => [
            '/legacy/cached-exact.htm' => '/projects/ahmic',
        ],
        'map' => null,
        'shield.enabled' => false,
    ]);
    $check = $redirects->checkForRedirect();

    expect($check)->not()->toBeNull();
    expect($check->to())->toEqual('/projects/ahmic');
    Redirects::flush();
});
test('unmatched requests are not written to the redirect cache', function () {
    Redirects::flush();

    $uri = '/random-public-miss?redirect-miss=one';
    $redirects = new Redirects([
        'site.url' => 'http://redirects.test/',
        'request.uri' => $uri,
        'map' => null,
    ]);

    $check = $redirects->checkForRedirect();

    expect($check)->toBeNull();
    expect(kirby()->cache('bnomei.redirects')->get(md5($uri)))->toBeNull();

    Redirects::flush();
});
test('wordpress block a', function () {
    $options = [
        'site.url' => 'http://redirects.test/',
        'request.uri' => '/wp-content/themes/test/index.js',
    ];
    $redirects = new Redirects($options);
    $check = $redirects->checkForRedirect();
    expect($check->code() === 404)->toBeTrue();
});
test('wordpress block b', function () {
    $options = [
        'site.url' => 'http://redirects.test/',
        'request.uri' => '/xmlrpc.php?action=pingback.ping',
    ];
    $redirects = new Redirects($options);
    $check = $redirects->checkForRedirect();
    expect($check->code() === 404)->toBeTrue();
});
test('shield redirects keep priority over user redirects', function () {
    $redirects = new Redirects([
        'site.url' => 'http://redirects.test/',
        'request.uri' => '/wp-login.php',
        'map' => [
            ['fromuri' => '/wp-login.php', 'touri' => '/login', 'code' => 301],
        ],
    ]);
    $check = $redirects->checkForRedirect();

    expect($check)->not()->toBeNull();
    expect($check->code())->toEqual(404);
    expect($check->to())->toEqual('');
});
test('append remove', function () {
    $redirects = new Redirects;
    $old = file_get_contents(__DIR__.'/content/site.txt');

    $hash = md5((string) time());
    $success = $redirects->append(
        ['fromuri' => '/old1-'.$hash, 'touri' => '/new1', 'code' => 302]
    );
    expect($success)->toBeTrue();
    $success = $redirects->append([
        ['fromuri' => '/old2-'.$hash, 'touri' => '/new2', 'code' => 302],
        ['fromuri' => '/old3-'.$hash, 'touri' => '/new3'],
    ]);
    expect($success)->toBeTrue();
    $this->assertStringContainsString($hash, file_get_contents(
        __DIR__.'/content/site.txt'
    ));

    $success = $redirects->remove(
        ['fromuri' => '/old1-'.$hash, 'touri' => '/new1']
    );
    expect($success)->toBeTrue();
    $success = $redirects->remove([
        ['fromuri' => '/old2-'.$hash, 'touri' => '/new2'],
        ['fromuri' => '/old3-'.$hash, 'touri' => '/new3'],
    ]);
    expect($success)->toBeTrue();
    $this->assertStringNotContainsString($hash, file_get_contents(
        __DIR__.'/content/site.txt'
    ));

    // can not update if is not a site/page
    $redirects = new Redirects([
        'map' => [],
    ]);
    $success = $redirects->append([['fromuri' => '/old-'.$hash, 'touri' => '/new']]);
    expect($success)->toBeFalse();

    // restore
    file_put_contents(__DIR__.'/content/site.txt', $old);
});
