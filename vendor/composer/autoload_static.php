<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit870d16a27eb3cc49be839865ae605739
{
    public static $prefixLengthsPsr4 = array (
        'K' => 
        array (
            'Kirby\\' => 6,
        ),
        'B' => 
        array (
            'Bnomei\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Kirby\\' => 
        array (
            0 => __DIR__ . '/..' . '/getkirby/composer-installer/src',
        ),
        'Bnomei\\' => 
        array (
            0 => __DIR__ . '/../..' . '/classes',
        ),
    );

    public static $classMap = array (
        'Bnomei\\Redirect' => __DIR__ . '/../..' . '/classes/Redirect.php',
        'Bnomei\\Redirects' => __DIR__ . '/../..' . '/classes/Redirects.php',
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Kirby\\ComposerInstaller\\CmsInstaller' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/CmsInstaller.php',
        'Kirby\\ComposerInstaller\\Installer' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/Installer.php',
        'Kirby\\ComposerInstaller\\Plugin' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/Plugin.php',
        'Kirby\\ComposerInstaller\\PluginInstaller' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/PluginInstaller.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit870d16a27eb3cc49be839865ae605739::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit870d16a27eb3cc49be839865ae605739::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit870d16a27eb3cc49be839865ae605739::$classMap;

        }, null, ClassLoader::class);
    }
}
