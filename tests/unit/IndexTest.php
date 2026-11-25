<?php

test('redirect codes route', function () {
    $response = kirby()->render('/plugin-redirects/codes');
    expect($response->code() === 200)->toBeTrue();
    expect($response->type())->toStartWith('application/json');
});

test('finds home page', function () {
    $response = kirby()->render('/');
    expect($response->code() === 200)->toBeTrue();
    $this->assertStringContainsString('Home', $response->body());
});

test('finds test page', function () {
    $response = kirby()->render('/projects/ahmic');
    expect($response->code() === 200)->toBeTrue();
    $this->assertStringContainsString('Ahmic', $response->body());
});
