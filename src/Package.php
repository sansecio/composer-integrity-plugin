<?php

namespace Sansec\Integrity;

class Package
{
    public function __construct(
        public readonly string $name,
        public readonly string $version
    ) {
    }
}
