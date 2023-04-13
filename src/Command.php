<?php

namespace Sansec\Integrity;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends BaseCommand
{
    private const VERDICT_TYPES = [
        'unknown' => '<fg=white>?</>',
        'good' => '<fg=green>✓</>',
        'bad' => '<fg=red>⨉</>'
    ];

    private Integrity $integrity;

    public function __construct(Integrity $integrity, string $name = null)
    {
        parent::__construct($name);
        $this->integrity = $integrity;
    }

    protected function configure()
    {
        $this->setName('integrity')->setDescription('Checks composer integrity.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table
            ->setHeaders(['Status', 'Package', 'Version', 'Checksum', 'Percentage'])
            ->setRows(array_map(fn(Verdict $packageVerdict) => [
                self::VERDICT_TYPES[$packageVerdict->verdict],
                $packageVerdict->name,
                $packageVerdict->version,
                $packageVerdict->checksum,
                $packageVerdict->percentage
            ], $this->integrity->getPackageVerdicts()));

        $table->setColumnStyle(0, (new TableStyle())->setPadType(STR_PAD_BOTH));
        $table->render();

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}