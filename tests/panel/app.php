<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

$state = getenv('REDIRECTS_PANEL_STATE') ?: __DIR__.'/state';

return new Kirby([
    'roots' => [
        'index' => __DIR__.'/public',
        'site' => $state.'/site',
        'content' => $state.'/content',
        'plugins' => dirname(__DIR__).'/site/plugins',
        'templates' => dirname(__DIR__).'/site/templates',
    ],
    'options' => [
        'url' => getenv('PUBLIC_URL') ?: 'http://localhost:8000',
    ],
]);
