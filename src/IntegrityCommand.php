<?php

namespace Sansec\ComposerIntegrity;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IntegrityCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('integrity')
            ->setDescription('Checks composer integrity.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Hello world!");
    }
}