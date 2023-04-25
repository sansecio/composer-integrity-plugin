<?php

namespace Sansec\Integrity\PatchPlugin;

use Composer\Console\Application;
use DI\Container;
use Sansec\Integrity\PatchPlugin;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class Vaimo implements PatchPlugin
{
    public function __construct(
        private readonly Application $application,
        private readonly Container $container,
    ) {
    }

    public function getPatchedPackages(): array
    {
        $command = $this->application->find('patch:list');
        $bufferedOutput = $this->container->make(BufferedOutput::class);
        $input = $this->container->make(ArrayInput::class, ['parameters' => ['--json' => true, '--status' => 'applied']]);
        try {
            $command->run($input, $bufferedOutput);
            return array_keys(json_decode($bufferedOutput->fetch(), true));
        } catch (\Exception $e) {
            return [];
        }
    }
}
