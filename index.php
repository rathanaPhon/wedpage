<?php
require __DIR__.'/src/Symfony/Component/Filesystem/Exception/ExceptionInterface.php';
require __DIR__.'/src/Symfony/Component/Filesystem/Exception/IOExceptionInterface.php';
require __DIR__.'/src/Symfony/Component/Filesystem/Exception/IOException.php';
require __DIR__.'/src/Symfony/Component/Filesystem/Filesystem.php';

use Symfony\Component\Filesystem\Filesystem;

/**
 * Links dependencies of a project to a local clone of the main symfony/symfony GitHub repository.
 *
 * @author phonrathana Dunglas <rathanaphon035@gmail.com>
 */

$copy = false !== $k = array_search('--copy', $argv, true);
$copy && array_splice($argv, $k, 1);
$callback = false !== $k = array_search('--rollback', $argv, true);
$callback && array_splice($argv, $k, 1);
$pathToProject = $argv[1] ?? getcwd();

if (!is_dir("$pathToProject/vendor/symfony")) {
    echo 'Links dependencies of a project to a local clone of the main symfony/symfony GitHub repository.'.PHP_EOL.PHP_EOL;
    echo "Usage: $argv[0] /path/to/the/project".PHP_EOL;
    echo '       Use `--copy` to copy dependencies instead of symlink'.PHP_EOL.PHP_EOL;
    echo '       Use `--callback` to callback'.PHP_EOL.PHP_EOL;
    echo "The directory \"$pathToProject\" does not exist or the dependencies are not installed, did you forget to run \"composer install\" in your project?".PHP_EOL;
    exit(1);
}
$sfPackages = array('symfony/symfony' => __DIR__);
$filesystem = new Filesystem();
$braces = array('Bundle', 'Bridge', 'Component', 'Component/Security', 'Component/Mailer/Bridge', 'Component/Messenger/Bridge', 'Component/Notifier/Bridge', 'Contracts', 'Component/Translation/Bridge');
$directories = array_merge(...array_values(array_map(function ($part) {
    return glob(__DIR__.'/src/Symfony/'.$part.'/*', GLOB_ONLYDIR | GLOB_NOSORT);
}, $braces)));
$directories[] = __DIR__.'/src/Symfony/Contracts';
foreach ($directories as $dir) {
    if ($filesystem->exists($composer = "$dir/composer.json")) {
        $sfPackages[json_decode($filesystem->readFile($composer), flags: JSON_THROW_ON_ERROR)->name] = $dir;
    }
}
foreach (glob("$pathToProject/vendor/symfony/*", GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
    $package = 'symfony/'.basename($dir);
    if (!isset($sfPackages[$package])) {
        continue;
    }
    if ($callback) {
        $filesystem->remove($dir);
        echo "\"$package\" has been rollback from \"$sfPackages[$package]\".".PHP_EOL;
        continue;
}
    if (!$copy && is_link($dir)) {
        echo "\"$package\" is already a symlink, skipping.".PHP_EOL;
        continue;
    }
    $sfDir = ('\\' === DIRECTORY_SEPARATOR || $copy) ? $sfPackages[$package] : $filesystem->makePathRelative($sfPackages[$package], dirname(realpath($dir)));
    $filesystem->remove($dir);

    if ($copy) {
        $filesystem->mirror($sfDir, $dir);
        echo "\"$package\" has been copied from \"$sfPackages[$package]\".".PHP_EOL;
    } else {
        $filesystem->symlink($sfDir, $dir);
        echo "\"$package\" has been linked to \"$sfPackages[$package]\".".PHP_EOL;
    }
}
foreach (glob("$pathToProject/var/cache/*", GLOB_NOSORT) as $cacheDir) {
    $filesystem->remove($cacheDir);
}
if ($callback) {
    echo PHP_EOL."callback done, do not forget to run \"composer install\" in your project \"$pathToProject\".".PHP_EOL;
}
