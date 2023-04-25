<?php

namespace Sansec\Integrity\PackageResolver;

use Composer\Composer;
use Sansec\Integrity\Package;
use Sansec\Integrity\PackageResolverStrategy;

class ComposerStrategy implements PackageResolverStrategy
{
    public function __construct(private readonly Composer $composer)
    {
    }

    public function resolvePackages(): array
    {
        $packages = [];
        foreach ($this->composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
            if ($package->getType() == 'metapackage') {
                continue;
            }

            if (strpos($package->getVersion(), 'dev-') === 0) {
                continue;
            }

            $packages[] = new Package($package->getName(), $package->getPrettyVersion());
        }
        return $packages;
    }

    public function resolveVendorPath(): string
    {
        return $this->composer->getConfig()->get('vendor-dir');
    }
}
