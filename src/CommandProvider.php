<?php

namespace Sansec\Integrity;

use Composer\Composer;
use GuzzleHttp\Client;

class CommandProvider implements \Composer\Plugin\Capability\CommandProvider
{
    private readonly Composer $composer;

    public function __construct(array $config)
    {
        $this->composer = $config['composer'];
    }

    public function getCommands()
    {
        return [
            new IntegrityCommand(
                new PackageSubmitter($this->composer, new Client(), new Hasher()),
                new PatchDetector($this->composer)
            )
        ];
    }
}

