<?php

namespace Sansec\Integrity;

use Composer\Command\BaseCommand;
use Composer\Composer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends BaseCommand
{
    private const HASHED_FILE_EXTENSIONS = [
        'php',
        'phtml',
        'html',
        'js'
    ];

    private Composer $composer;

    public function __construct(Composer $composer, string $name = null)
    {
        parent::__construct($name);
        $this->composer = $composer;
    }

    protected function configure()
    {
        $this->setName('integrity')->setDescription('Checks composer integrity.');
    }

    private function getVendorDirectory(): string
    {
        return $this->composer->getConfig()->get('vendor-dir');
    }

    private function getPackageFiles($dir): array {
        $files = [];
        $items = scandir($dir);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_link($path)) {
                continue;
            } elseif (is_dir($path)) {
                $files = array_merge($files, $this->getPackageFiles($path));
            } elseif (in_array(strtolower(pathinfo($item, PATHINFO_EXTENSION)), self::HASHED_FILE_EXTENSIONS)) {
                $files[] = $path;
            }
        }

        return $files;
    }

    private function getPackageDataHash(string $packageDirectory): string
    {
        $context = hash_init('xxh64');
        foreach ($this->getPackageFiles($packageDirectory) as $file) {
            hash_update_file($context, $file);
        }
        return strtoupper(hash_final($context));
    }

    private function getPackageIdHash(string $packageName, string $packageVersion): string
    {
        $context = hash_init('xxh64');
        hash_update($context, $packageName);
        hash_update($context, $packageVersion);
        return strtoupper(hash_final($context));
    }

    private function getInstallIdHash(string $baseDir): string
    {
        $context = hash_init('xxh64');
        hash_update($context, file_get_contents(implode(DIRECTORY_SEPARATOR, [$baseDir, 'composer.json'])));
        hash_update($context, file_get_contents(implode(DIRECTORY_SEPARATOR, [$baseDir, 'composer.lock'])));
        hash_update($context, 'integrity-plugin');
        return strtoupper(hash_final($context));
    }

    private function getVendorState(): array
    {
        $packages = [];
        foreach ($this->composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
            $packagePath = implode(DIRECTORY_SEPARATOR, [$this->getVendorDirectory(), $package->getName()]);

            $packages[] = [
                'id' => $this->getPackageIdHash($package->getName(), $package->getPrettyVersion()),
                'data' => $this->getPackageDataHash($packagePath)
            ];
        }

        return [
            'id'        => $this->getInstallIdHash(getcwd()),
            'hash_type' => 0, // xxh64
            'origin'    => 1,
            'pkg'       => $packages
        ];
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        var_dump($this->getVendorState());

        return 0;
    }
}