<?php

namespace Sansec\Integrity;

use Composer\Composer;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class PackageSubmitter
{
    private const API_URL = 'https://api.sansec.io/v1/composer/integrity';

    public function __construct(
        private readonly Composer $composer,
        private readonly Client $client,
        private readonly Hasher $hasher
    ) {}

    private function getVendorDirectory(): string
    {
        return $this->composer->getConfig()->get('vendor-dir');
    }

    private function getPackages(): array
    {
        $packages = [];
        foreach ($this->composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
            $packagePath = implode(DIRECTORY_SEPARATOR, [$this->getVendorDirectory(), $package->getName()]);

            $packages[] = [
                'id'      => $this->hasher->generatePackageIdHash($package->getName(), $package->getPrettyVersion()),
                'data'    => $this->hasher->generatePackageDataHash($packagePath),
                'name'    => $package->getName(),
                'version' => $package->getPrettyVersion()
            ];
        }
        return $packages;
    }

    private function getVendorState(array $packages): array
    {
        return [
            'id'        => $this->hasher->generateInstallIdHash(getcwd()),
            'hash_type' => 0, // xxh64
            'origin'    => 1,
            'pkg'       => array_map(
                fn(array $package) => [
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

    public function getPackageVerdicts(): array
    {
        $packages = $this->getPackages();
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