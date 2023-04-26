<?php

namespace Sansec\Integrity;

use Composer\Composer;
use DI\Container;

class ComposerIntegrityCommand extends IntegrityCommand
{
    private ?PatchDetector $patchDetector = null;

    public function __construct(
        private readonly Container $container,
        private readonly PackageResolverStrategy $packageResolverStrategy,
        private readonly Composer $composer,
        string $name = null
    ) {
        parent::__construct($container, $packageResolverStrategy, $name);
    }

    private function getPatchDetector(): PatchDetector
    {
        if ($this->patchDetector === null) {
            $this->patchDetector = $this->container->make(
                PatchDetector::class,
                [
                    'composer' => $this->composer,
                    // We must use a getter because the application object is not known to us during construction
                    'application' => $this->getApplication()
                ]
            );
        }
        return $this->patchDetector;
    }

    protected function getRendererOptions(bool $json): array
    {
        $options = parent::getRendererOptions($json);

        if (!$this->getPatchDetector()->hasPatchPlugin()) {
            return $options;
        }

        $options['additionalColumns'] = ['Patched'];
        $options['verdictEnricher'] = new class($options['json'], $this->getPatchDetector()->getPatchedPackages()) implements VerdictEnricher
        {
            public function __construct(private readonly bool $json, private readonly array $patchedPackages)
            {
            }

            public function enrich(PackageVerdict $packageVerdict): array
            {
                $patchApplied = in_array($packageVerdict->name, $this->patchedPackages);
                return ['patch_applied' => $this->json ? $patchApplied : ($patchApplied ? 'Yes' : 'No')];
            }
        };

        return $options;
    }
}
