<?php

namespace Sansec\Integrity;

use Composer\Composer;

class CommandProvider implements \Composer\Plugin\Capability\CommandProvider
{
    private Composer $composer;

    public function __construct(array $config)
    {
        $this->composer = $config['composer'];
    }

    public function getCommands()
    {
        return [
            new Command($this->composer)
        ];
    }
}