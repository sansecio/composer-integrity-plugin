<?php

namespace Sansec\Integrity;

use Composer\Composer;
use Composer\Console\Application;
use DI\Container;
use Sansec\Integrity\PatchPlugin\Cweagans;
use Sansec\Integrity\PatchPlugin\Vaimo;

class PatchDetector
{
    private const PATCH_PLUGIN_HANDLERS = [
        'vaimo/composer-patches' => Vaimo::class,
        'cweagans/composer-patches' => Cweagans::class,
    ];

    public function __construct(
        private readonly Container $container,
        private readonly Composer $composer,
        private readonly Application $application,
    ) {
    }

    private function getPatchPlugin(): ?PatchPluginInterface
    {
        $composerPackages = $this->composer->getRepositoryManager()->getLocalRepository()->getPackages();
        foreach ($composerPackages as $package) {
            if (in_array($package->getName(), array_keys(self::PATCH_PLUGIN_HANDLERS))) {
                return $this->container->make(
                    self::PATCH_PLUGIN_HANDLERS[$package->getName()],
                    [
                        'application' => $this->application,
                        'composer' => $this->composer
                    ]
                );
            }
        }
        return null;
    }

    public function getPatchedPackages(): array
    {
        $patchPlugin = $this->getPatchPlugin();
        return $patchPlugin !== null ? $patchPlugin->getPatchedPackages() : [];
    }

    public function hasPatchPlugin(): bool
    {
        return $this->getPatchPlugin() !== null;
    }
}
