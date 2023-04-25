<?php

namespace Sansec\Integrity\PackageResolver;

use RuntimeException;
use Sansec\Integrity\Package;
use Sansec\Integrity\PackageResolverStrategy;

class LockReaderStrategy implements PackageResolverStrategy
{
    public function __construct(private readonly string $rootDirectory)
    {
    }

    public function resolveVendorPath(): string
    {
        return implode(DIRECTORY_SEPARATOR, [$this->rootDirectory, 'vendor']);
    }

    public function resolvePackages(): array
    {
        $lockFile = implode(DIRECTORY_SEPARATOR, [$this->rootDirectory, 'composer.lock']);
        if (!file_exists($lockFile)) {
            throw new RuntimeException('Could not find composer.lock');
        }

        $data = json_decode(file_get_contents($lockFile), true);

        $packages = [];
        foreach ($data['packages'] ?? [] as $package) {
            if ($package['type'] == 'metapackage') {
                continue;
            }

            if (strpos($package['version'], 'dev-') === 0) {
                continue;
            }

            $packages[] = new Package($package['name'], $package['version']);
        }

        return $packages;
    }
}
