<?php

namespace Sansec\Integrity;

interface VerdictEnricher
{
    public function enrich(PackageVerdict $packageVerdict): array;
}
