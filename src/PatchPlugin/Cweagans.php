<?php

namespace Sansec\Integrity\PatchPlugin;

use Sansec\Integrity\PatchPluginInterface;

class Cweagans implements PatchPluginInterface
{
    public function getPatchedPackages(): array
    {
        return [];
    }
}
