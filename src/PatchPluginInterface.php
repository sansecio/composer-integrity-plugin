<?php

namespace Sansec\Integrity;

interface PatchPluginInterface
{
    public function getPatchedPackages(): array;
}
