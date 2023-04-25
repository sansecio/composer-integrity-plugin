<?php

namespace Sansec\Integrity;

use Composer\Command\BaseCommand;
use Composer\Composer;
use DI\Container;
use Sansec\Integrity\PackageResolver\ComposerStrategy;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IntegrityCommand extends BaseCommand
{
    private const VERDICT_TYPES = [
        'unknown' => '<fg=white>?</>',
        'match' => '<fg=green>✓</>',
        'mismatch' => '<fg=red>⨉</>'
    ];

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

    private function getPercentage(PackageVerdict $packageVerdict): string
    {
        if ($packageVerdict->verdict == 'unknown') {
            return '-';
        }

        return $packageVerdict->percentage . '%';
    }

    private function renderIntegrityTable(OutputInterface $output, array $headers, array $rows): void
    {
        $table = (new Table($output))->setHeaders($headers)->setRows($rows);
        foreach ([0, 5, 6] as $centeredColumnId) {
            $table->setColumnStyle($centeredColumnId, (new TableStyle())->setPadType(STR_PAD_BOTH));
        }
        $table->render();
    }

    private function hasMismatchingVerdicts(array $verdicts): bool
    {
        return count(array_filter($verdicts, fn (PackageVerdict $verdict) => $verdict->verdict == 'mismatch')) > 0;
    }

    private function getRowsFromVerdicts(array $verdicts, bool $json): array
    {
        $hasPatchPlugin = $this->getPatchDetector()->hasPatchPlugin();
        $patchedPackages = $this->getPatchDetector()->getPatchedPackages();

        return array_map(function (PackageVerdict $packageVerdict) use ($json, $hasPatchPlugin, $patchedPackages) {
            $row = [
                'status' => $json ? $packageVerdict->verdict : self::VERDICT_TYPES[$packageVerdict->verdict],
                'package' => $packageVerdict->name,
                'version' => $packageVerdict->version,
                'package_id' => $packageVerdict->id,
                'checksum' => $packageVerdict->checksum,
                'percentage' => $json ? (float) $packageVerdict->percentage : $this->getPercentage($packageVerdict)
            ];

            if ($hasPatchPlugin) {
                $patchApplied = in_array($packageVerdict->name, $patchedPackages);
                $row['patch_applied'] = $json ? $patchApplied : ($patchApplied ? 'Yes' : 'No');
            }

            return $row;
        }, $verdicts);
    }

    private function getRowHeaders(): array
    {
        $headers = [
            'Status',
            'Package',
            'Version',
            'Package ID',
            'Checksum',
            'Percentage',
        ];

        if ($this->getPatchDetector()->hasPatchPlugin()) {
            $headers[] = 'Patch applied?';
        }

        return $headers;
    }

    private function filterMatchVerdicts(array $verdicts): array
    {
        return array_filter($verdicts, fn (PackageVerdict $verdict) => $verdict->verdict != 'match');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $verdicts = $this->getPackageSubmitter()->getPackageVerdicts($output);

        if ($input->getOption(self::OPTION_NAME_SKIP_MATCH) !== false) {
            $verdicts = $this->filterMatchVerdicts($verdicts);
        }

        $json = (bool) $input->getOption(self::OPTION_NAME_JSON);
        $rows = $this->getRowsFromVerdicts($verdicts, $json);

        if ($json) {
            echo json_encode($rows, JSON_PRETTY_PRINT);
        } else {
            $this->renderIntegrityTable($output, $this->getRowHeaders(), $rows);
        }

        return $this->hasMismatchingVerdicts($verdicts) ? Command::FAILURE : Command::SUCCESS;
    }
}
