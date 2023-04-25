<?php

namespace Sansec\Integrity;

use Composer\Command\BaseCommand;
use Composer\Composer;
use DI\Container;
use Sansec\Integrity\PackageResolver\ComposerStrategy;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IntegrityCommand extends BaseCommand
{
    private const OPTION_NAME_SKIP_MATCH = 'skip-match';
    private const OPTION_NAME_JSON = 'json';

    private ?PackageSubmitter $packageSubmitter = null;
    private ?PatchDetector $patchDetector = null;

    public function __construct(
        private readonly Container $container,
        private readonly Composer $composer,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('integrity')
            ->setDescription('Checks composer integrity.')
            ->addOption(self::OPTION_NAME_JSON, null, InputOption::VALUE_NONE, 'Show output in JSON format')
            ->addOption(self::OPTION_NAME_SKIP_MATCH, null, InputOption::VALUE_OPTIONAL, 'Skip matching checksums.', false);
    }

    private function getPackageSubmitter(): PackageSubmitter
    {
        if ($this->packageSubmitter === null) {
            $this->packageSubmitter = $this->container->make(
                PackageSubmitter::class,
                [
                    'packageResolverStrategy' => $this->container->make(
                        ComposerStrategy::class,
                        ['composer' => $this->composer]
                    )
                ]
            );
        }
        return $this->packageSubmitter;
    }

    private function getPatchDetector(): PatchDetector
    {
        // We must use a getter because the application object is not known to us during construction
        if ($this->patchDetector === null) {
            $this->patchDetector = $this->container->make(
                PatchDetector::class,
                [
                    'composer' => $this->composer,
                    'application' => $this->getApplication()
                ]
            );
        }
        return $this->patchDetector;
    }

    private function hasMismatchingVerdicts(array $verdicts): bool
    {
        return count(array_filter($verdicts, fn (PackageVerdict $verdict) => $verdict->verdict == 'mismatch')) > 0;
    }


    private function filterMatchVerdicts(array $verdicts): array
    {
        return array_filter($verdicts, fn (PackageVerdict $verdict) => $verdict->verdict != 'match');
    }

    private function getRendererOptions(bool $json): array
    {
        $hasPatchPlugin = $this->getPatchDetector()->hasPatchPlugin();
        $patchedPackages = $this->getPatchDetector()->getPatchedPackages();

        $options = ['json' => $json];
        if ($hasPatchPlugin) {
            $options['additionalColumns'] = ['Patched'];
            $options['verdictEnricher'] = new class($options['json'], $patchedPackages) implements VerdictEnricher
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
        }

        return $options;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $verdicts = $this->getPackageSubmitter()->getPackageVerdicts($output);

        if ($input->getOption(self::OPTION_NAME_SKIP_MATCH) !== false) {
            $verdicts = $this->filterMatchVerdicts($verdicts);
        }

        $verdictRenderer = $this->container->make(
            VerdictRenderer::class,
            $this->getRendererOptions((bool) $input->getOption(self::OPTION_NAME_JSON))
        );
        $verdictRenderer->render($output, $verdicts);

        return $this->hasMismatchingVerdicts($verdicts) ? Command::FAILURE : Command::SUCCESS;
    }
}
