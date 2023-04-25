<?php

namespace Sansec\Integrity\PatchPlugin;

use Composer\Composer;
use Composer\IO\BufferIO;
use Sansec\Integrity\PatchPlugin;
use cweagans\Composer\Patches;

class Cweagans implements PatchPlugin
{
    public function __construct(
        private readonly Composer $composer,
        private readonly BufferIO $io,
        private readonly Patches $patches
    ) {
    }

    public function getPatchedPackages(): array
    {
        $this->patches->activate($this->composer, $this->io);
        return array_keys($this->patches->grabPatches());
    }
}
