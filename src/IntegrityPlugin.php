<?php

namespace Sansec\ComposerIntegrity;
use Composer\Plugin\Capability\CommandProvider;

class IntegrityPlugin implements CommandProvider
{
    public function getCommands()
    {
        return [
            new IntegrityCommand()
        ];
    }
}