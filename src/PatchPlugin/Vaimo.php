<?php

namespace Sansec\Integrity\PatchPlugin;

use Sansec\Integrity\PatchPluginInterface;

class Vaimo implements PatchPluginInterface
{
    public function getPatchedPackages(): array
    {
        return [];
    }
}
