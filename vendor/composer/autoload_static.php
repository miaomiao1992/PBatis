<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitca3690eed12be916f846f0fe7c180a35
{
    public static $prefixLengthsPsr4 = array (
        'p' => 
        array (
            'pbatis\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'pbatis\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitca3690eed12be916f846f0fe7c180a35::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitca3690eed12be916f846f0fe7c180a35::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitca3690eed12be916f846f0fe7c180a35::$classMap;

        }, null, ClassLoader::class);
    }
}
