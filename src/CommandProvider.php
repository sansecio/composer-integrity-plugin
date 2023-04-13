<?php

namespace Sansec\Integrity;

use Composer\Composer;
use GuzzleHttp\Client;

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
            new Command(new Integrity($this->composer, new Client()))
        ];
    }
}