<?php

namespace Sansec\Integrity;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Command\Command;
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

    private const OPTION_NAME_SKIP_MATCH = 'skip-match';

    private const OPTION_NAME_JSON = 'json';

    public function __construct(private readonly PackageSubmitter $submitter, string $name = null)
    {
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $command = $this->getApplication()->find('patch:list');
            $bufferedOutput = new BufferedOutput();
            $rawJson = $command->run(new ArrayInput(['--json' => true, '--status' => 'applied']), $bufferedOutput);
            $appliedPatches = array_keys(json_decode($bufferedOutput->fetch(), true));
        } catch (\Exception $e) {
            $appliedPatches = [];
        }

        $verdicts = $this->submitter->getPackageVerdicts($output);

        if ($input->getOption(self::OPTION_NAME_SKIP_MATCH) !== false) {
            $verdicts = array_filter($verdicts, fn (PackageVerdict $verdict) => $verdict->verdict != 'match');
        }

        $json = $input->getOption(self::OPTION_NAME_JSON);

        $headers = ['Status', 'Package', 'Version', 'Package ID', 'Checksum', 'Percentage', 'Patch applied?'];

        $rows = array_map(fn (PackageVerdict $packageVerdict) => [
            'status' => $json ? $packageVerdict->verdict : self::VERDICT_TYPES[$packageVerdict->verdict],
            'package' => $packageVerdict->name,
            'version' => $packageVerdict->version,
            'package_id' => $packageVerdict->id,
            'checksum' => $packageVerdict->checksum,
            'percentage' => $json ? $packageVerdict->percentage : $this->getPercentage($packageVerdict),
            'patch_applied' => $json ? in_array($packageVerdict->name, $appliedPatches) : (in_array($packageVerdict->name, $appliedPatches) ? 'Yes' : 'No')
        ], $verdicts);

        if ($json) {
            echo json_encode($rows, JSON_PRETTY_PRINT);
        } else {
            $table = new Table($output);
            $table
                ->setHeaders($headers)
                ->setRows($rows);

            foreach ([0, 5] as $centeredColumnId) {
                $table->setColumnStyle($centeredColumnId, (new TableStyle())->setPadType(STR_PAD_BOTH));
            }
            $table->render();
        }

        $mismatching = array_filter($verdicts, fn (PackageVerdict $verdict) => $verdict->verdict == 'mismatch');

        return count($mismatching) > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
