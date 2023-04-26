<?php

namespace Sansec\Integrity;

use Composer\Command\BaseCommand;
use DI\Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IntegrityCommand extends BaseCommand
{
    private const OPTION_NAME_SKIP_MATCH = 'skip-match';
    private const OPTION_NAME_JSON = 'json';

    private ?PackageSubmitter $packageSubmitter = null;

    public function __construct(
        private readonly Container $container,
        private readonly PackageResolverStrategy $packageResolverStrategy,
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
                ['packageResolverStrategy' => $this->packageResolverStrategy]
            );
        }
        return $this->packageSubmitter;
    }

    private function hasMismatchingVerdicts(array $verdicts): bool
    {
        return count(array_filter($verdicts, fn (PackageVerdict $verdict) => $verdict->verdict == 'mismatch')) > 0;
    }


    private function filterMatchVerdicts(array $verdicts): array
    {
        return array_filter($verdicts, fn (PackageVerdict $verdict) => $verdict->verdict != 'match');
    }

    protected function getRendererOptions(bool $json): array
    {
        return ['json' => $json];
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
