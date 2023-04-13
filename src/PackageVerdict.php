<?php

namespace Sansec\Integrity;

class PackageVerdict
{
    public function __construct(
        public readonly string $name,
        public readonly string $version,
        public readonly string $checksum,
        public readonly string $percentage,
        public readonly string $verdict
    ) {}
}