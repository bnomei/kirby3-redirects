<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitb6cc1529004ec5e8322f3866ff7b344e
{
    public static $prefixLengthsPsr4 = array (
        'B' => 
        array (
            'Bnomei\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Bnomei\\' => 
        array (
            0 => __DIR__ . '/../..' . '/classes',
        ),
    );

    public static $classMap = array (
        'Bnomei\\Redirects' => __DIR__ . '/../..' . '/classes/Redirects.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitb6cc1529004ec5e8322f3866ff7b344e::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitb6cc1529004ec5e8322f3866ff7b344e::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitb6cc1529004ec5e8322f3866ff7b344e::$classMap;

        }, null, ClassLoader::class);
    }
}