<?php

namespace Sansec\Integrity;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;

class Plugin implements Capable, PluginInterface
{
    public function getCapabilities()
    {
        return [\Composer\Plugin\Capability\CommandProvider::class => CommandProvider::class];
    }

    public function activate(Composer $composer, IOInterface $io)
    {
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
    }
}