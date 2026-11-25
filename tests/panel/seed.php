<?php

use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;

$kirby = require __DIR__.'/app.php';

if (! is_file($kirby->root('content').'/site.txt')) {
    Dir::copy(dirname(__DIR__).'/content/home', $kirby->root('content').'/home');
    Dir::copy(dirname(__DIR__).'/content/projects', $kirby->root('content').'/projects');
    F::write($kirby->root('content').'/site.txt', "Title: Redirects demo\n\n----\n\nRedirects:\n\n- fromuri: /old-project\n  touri: /projects/ahmic\n  code: _301\n");
}

if (! is_file($kirby->root('blueprints').'/site.yml')) {
    Dir::copy(dirname(__DIR__).'/site/blueprints', $kirby->root('blueprints'));
}

if ($kirby->users()->isEmpty()) {
    $password = bin2hex(random_bytes(16));
    $kirby->impersonate('kirby', fn () => $kirby->users()->create([
        'email' => 'editor@example.test',
        'password' => $password,
        'role' => 'admin',
    ]));
    $file = dirname($kirby->root('site')).'/password';
    umask(0077);
    file_put_contents($file, $password);
}
