<?php

require_once __DIR__.'/../vendor/autoload.php';

use Bnomei\Redirect;
use Bnomei\Redirects;

beforeEach(function () {
    $this->exampleOK = new Redirect('/old', '/', 302);
});
test('construct', function () {
    expect($this->exampleOK)->toBeInstanceOf(Redirect::class);
});
test('from', function () {
    expect($this->exampleOK->from())->toEqual('/old');
});
test('to', function () {
    expect($this->exampleOK->to())->toEqual('/');
});
test('code', function () {
    expect($this->exampleOK->code())->toEqual(302);
});
test('to array', function () {
    expect($this->exampleOK->toArray())->toHaveCount(3);
});
test('matches', function () {
    expect($this->exampleOK->matches('/old'))->toBeTrue();
    expect($this->exampleOK->matches('/old/'))->toBeTrue();

    expect($this->exampleOK->matches('old'))->toBeFalse();
    expect($this->exampleOK->matches('/other'))->toBeFalse();
});
test('normalize code', function () {
    expect(Redirect::normalizeCode(null))->toEqual(301);
    expect(Redirect::normalizeCode(0))->toEqual(301);
    expect(Redirect::normalizeCode('null'))->toEqual(301);
    expect(Redirect::normalizeCode('false'))->toEqual(301);
    expect(Redirect::normalizeCode(''))->toEqual(301);
    expect(Redirect::normalizeCode(302))->toEqual(302);
    expect(Redirect::normalizeCode('302'))->toEqual(302);
    expect(Redirect::normalizeCode('_302'))->toEqual(302);
});
test('url', function () {
    // url
    expect(Redirect::url('https://example.net'))->toEqual('https://example.net');

    // path but not page
    expect(Redirect::url('relative'))->toEqual('/relative');

    // existing pages
    expect(Redirect::url('/'))->toEqual('/');
    expect(Redirect::url('home'))->toEqual('/');
    expect(Redirect::url('/home'))->toEqual('/');
    expect(Redirect::url('/projects'))->toEqual('/projects');
    expect(Redirect::url('projects'))->toEqual('/projects');
    expect(Redirect::url('projects/ahmic'))->toEqual('/projects/ahmic');
});
test('debug info', function () {
    expect($this->exampleOK->__debugInfo())->toBeArray();
});
test('redirects regex', function () {
    // regex
    $r = new Redirects([
        'request.uri' => '/some/old.html',
    ]);
    $check = $r->checkForRedirect();

    expect($check)->not->toBeNull();
    expect($check->code())->toEqual(304);
});
test('redirects regex placeholders', function () {
    // regex placeholders
    $r = new Redirects([
        'request.uri' => '/blog/2022_some-sLug.html',
    ]);
    $check = $r->checkForRedirect();

    expect($check)->not->toBeNull();
    expect($check->code())->toEqual(303);
});
test('redirects non301', function () {
    // non 301
    $r = new Redirects([
        'request.uri' => '/teapot',
    ]);
    $check = $r->checkForRedirect();

    expect($check)->not->toBeNull();
    expect($check->code())->toEqual(418);
});
