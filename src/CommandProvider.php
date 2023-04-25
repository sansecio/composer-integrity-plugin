<?php

namespace Sansec\Integrity;

use Composer\Composer;
use DI\Container;

class CommandProvider implements \Composer\Plugin\Capability\CommandProvider
{
    private readonly Container $container;
    private readonly Composer $composer;

    public function __construct(array $config)
    {
        $this->container = new Container();
        $this->composer = $config['composer'];
    }

    public function getCommands()
    {
        return [
            $this->container->make(IntegrityCommand::class, ['composer' => $this->composer])
        ];
    }
}
