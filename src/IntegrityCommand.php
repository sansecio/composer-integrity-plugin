<?php

namespace Sansec\Integrity;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\NamespaceNotFoundException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class IntegrityCommand extends BaseCommand
{
    private const VERDICT_TYPES = [
        'unknown' => '<fg=white>?</>',
        'match' => '<fg=green>✓</>',
        'mismatch' => '<fg=red>⨉</>'
    ];

    private array $appliedPatches = [];

    private const OPTION_NAME_SKIP_MATCH = 'skip-match';

    private const OPTION_NAME_JSON = 'json';

    public function __construct(
        private readonly PackageSubmitter $submitter,
        private readonly PatchDetector $patchDetector,
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

    private function getPercentage(PackageVerdict $packageVerdict): string
    {
        if ($packageVerdict->verdict == 'unknown') {
            return '-';
        }

        return $packageVerdict->percentage . '%';
    }

    private function getAppliedPatches(): array
    {
        try {
            // vaimo/composer-patches
            $command = $this->getApplication()->find('patch:list');
            $bufferedOutput = new BufferedOutput();
            $command->run(new ArrayInput(['--json' => true, '--status' => 'applied']), $bufferedOutput);
            return array_keys(json_decode($bufferedOutput->fetch(), true));
        } catch (NamespaceNotFoundException $e) {
            // cweagans/composer-patches
            if (class_exists(cweagans\Composer\Patches::class)) {
                $cweagansComposerPatches = new \cweagans\Composer\Patches();
                $cweagansComposerPatches->activate($this->tryComposer(), $this->getIO());
                return array_keys($cweagansComposerPatches->grabPatches());
            } else {
                throw new \Exception('cweagans/composer-patches not found');
            }
        } catch (\Exception $e) {
            return [];
        }
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
        $hasPatchPlugin = $this->patchDetector->hasPatchPlugin();
        $patchedPackages = $this->patchDetector->getPatchedPackages();

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
        if ($this->patchDetector->hasPatchPlugin()) {
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
        $verdicts = $this->submitter->getPackageVerdicts($output);

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
