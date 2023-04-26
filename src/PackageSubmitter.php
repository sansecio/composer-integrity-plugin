<?php

namespace Sansec\Integrity;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class PackageSubmitter
{
    private const API_URL = 'https://api.sansec.io/v1/composer/integrity';

    public function __construct(
        private readonly PackageResolverStrategy $packageResolverStrategy,
        private readonly Client $client,
        private readonly Hasher $hasher
    ) {
    }

    private function getPackages(OutputInterface $output): array
    {
        $composerPackages = $this->packageResolverStrategy->resolvePackages();
        $progressBar = new ProgressBar($output, count($composerPackages));
        $progressBar->setRedrawFrequency(1);

        $packages = [];
        foreach ($composerPackages as $package) {
            $progressBar->advance();

            $packagePath = implode(DIRECTORY_SEPARATOR, [$this->packageResolverStrategy->resolveVendorPath(), $package->name]);

            $packages[] = [
                'id'      => $this->hasher->generatePackageIdHash($package->name, $package->version),
                'data'    => $this->hasher->generatePackageDataHash($packagePath),
                'name'    => $package->name,
                'version' => $package->version
            ];
        }

        $progressBar->clear();

        return $packages;
    }

    private function getVendorState(array $packages): array
    {
        return [
            'id'        => $this->hasher->generateInstallIdHash(
                $this->packageResolverStrategy->resolveRootPath()
            ),
            'hash_type' => 0, // xxh64
            'origin'    => 1,
            'pkg'       => array_map(
                fn (array $package) => [
                    'id'   => $package['id'],
                    'data' => $package['data']
                ],
                $packages
            )
        ];
    }

    private function submitPackages(array $packages): array
    {
        $response = $this->client->post(
            self::API_URL,
            [RequestOptions::JSON => $this->getVendorState($packages)]
        );

        $result = json_decode($response->getBody()->getContents(), true);
        if (!isset($result['verdicts'])) {
            return [];
        }

        $verdictsByPackageVer = [];
        foreach ($result['verdicts'] as $verdict) {
            $verdictsByPackageVer[$verdict['pkg_ver']] = $verdict;
        }
        return $verdictsByPackageVer;
    }

    public function getPackageVerdicts(OutputInterface $output): array
    {
        $packages = $this->getPackages($output);
        $verdicts = $this->submitPackages($packages);

        $packageVerdicts = [];
        foreach ($packages as $package) {
            $packageVerdicts[] = new PackageVerdict(
                $package['name'],
                $package['version'],
                $package['data'],
                $package['id'],
                $verdicts[$package['id']]['incidence_perc'] ?? '-',
                $verdicts[$package['id']]['verdict'] ?? 'unknown'
            );
        }
        return $packageVerdicts;
    }
}
