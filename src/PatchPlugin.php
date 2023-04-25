<?php

namespace Sansec\Integrity;

interface PatchPlugin
{
    public function getPatchedPackages(): array;
}
