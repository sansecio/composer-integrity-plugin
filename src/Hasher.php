<?php

namespace Sansec\Integrity;

class Hasher
{
    private const HASHED_FILE_EXTENSIONS = [
        'php',
        'phtml',
        'html',
        'js'
    ];

    private const CLIENT_NAME = 'composer-integrity-plugin';

    private function getPackageFiles(string $dir): array
    {
        $files = [];
        $items = scandir($dir);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_link($path)) {
                continue;
            } elseif (is_dir($path)) {
                $files = array_merge($files, $this->getPackageFiles($path));
            } elseif (in_array(strtolower(pathinfo($item, PATHINFO_EXTENSION)), self::HASHED_FILE_EXTENSIONS)) {
                $files[] = $path;
            }
        }

        return $files;
    }

    public function generatePackageDataHash(string $packageDirectory): string
    {
        $context = hash_init('xxh64');
        foreach ($this->getPackageFiles($packageDirectory) as $file) {
            hash_update_file($context, $file);
        }
        return strtoupper(hash_final($context));
    }

    public function generatePackageIdHash(string $packageName, string $packageVersion): string
    {
        $context = hash_init('xxh64');
        hash_update($context, $packageName);
        hash_update($context, $packageVersion);
        return strtoupper(hash_final($context));
    }

    public function generateInstallIdHash(string $baseDir): string
    {
        $context = hash_init('xxh64');
        hash_update($context, file_get_contents(implode(DIRECTORY_SEPARATOR, [$baseDir, 'composer.json'])));
        hash_update($context, file_get_contents(implode(DIRECTORY_SEPARATOR, [$baseDir, 'composer.lock'])));
        hash_update($context, self::CLIENT_NAME);
        return strtoupper(hash_final($context));
    }
}