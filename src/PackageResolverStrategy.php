<?php

namespace Sansec\Integrity;

interface PackageResolverStrategy
{
    public function resolveVendorPath(): string;
    public function resolvePackages(): array;
}
