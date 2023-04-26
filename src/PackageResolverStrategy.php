<?php

namespace Sansec\Integrity;

interface PackageResolverStrategy
{
    public function resolvePackages(): array;
    public function resolveRootPath(): string;
    public function resolveVendorPath(): string;
}
