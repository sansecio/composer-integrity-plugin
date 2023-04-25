<?php

namespace Sansec\Integrity;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Output\OutputInterface;

class VerdictRenderer
{
    private const VERDICT_TYPES = [
        'unknown' => '<fg=white>?</>',
        'match' => '<fg=green>✓</>',
        'mismatch' => '<fg=red>⨉</>'
    ];

    public function __construct(
        private readonly bool $json = false,
        private readonly array $additionalColumns = [],
        private readonly ?VerdictEnricher $verdictEnricher = null
    ) {
    }

    private function getColumns(): array
    {
        $columns = [
            'Status',
            'Package',
            'Version',
            'Package ID',
            'Checksum',
            'Percentage',
        ];

        if (count($this->additionalColumns)) {
            $columns = array_merge($columns, $this->additionalColumns);
        }

        return $columns;
    }

    private function getPercentage(PackageVerdict $packageVerdict): string
    {
        if ($packageVerdict->verdict == 'unknown') {
            return '-';
        }

        return $packageVerdict->percentage . '%';
    }

    private function getRowsFromVerdicts(array $verdicts): array
    {
        return array_map(function (PackageVerdict $packageVerdict) {
            $row = [
                'status' => $this->json ? $packageVerdict->verdict : self::VERDICT_TYPES[$packageVerdict->verdict],
                'package' => $packageVerdict->name,
                'version' => $packageVerdict->version,
                'package_id' => $packageVerdict->id,
                'checksum' => $packageVerdict->checksum,
                'percentage' => $this->json ? (float) $packageVerdict->percentage : $this->getPercentage($packageVerdict)
            ];

            if ($this->verdictEnricher !== null) {
                $row = array_merge($row, $this->verdictEnricher->enrich($packageVerdict));
            }

            return $row;
        }, $verdicts);
    }

    private function renderTable(OutputInterface $output, array $rows): void
    {
        $table = (new Table($output))->setHeaders($this->getColumns())->setRows($rows);
        foreach ([0, 5, 6] as $centeredColumnId) {
            $table->setColumnStyle($centeredColumnId, (new TableStyle())->setPadType(STR_PAD_BOTH));
        }
        $table->render();
    }

    public function render(OutputInterface $output, array $verdicts)
    {
        $rows = $this->getRowsFromVerdicts($verdicts);
        if ($this->json) {
            $output->writeln(json_encode($rows, JSON_PRETTY_PRINT));
        } else {
            $this->renderTable($output, $rows);
        }
    }
}
