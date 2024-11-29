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
test('append remove', function () {
    $redirects = new Redirects;

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
