<?php

namespace Sansec\Integrity;

use Composer\Composer;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class Integrity
{
    private const API_URL = 'https://api.sansec.io/v1/vendor/integrity';

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
                'id' => $this->hasher->generatePackageIdHash($package->getName(), $package->getPrettyVersion()),
                'data' => $this->hasher->generatePackageDataHash($packagePath),
                'name' => $package->getName(),
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
                fn(array $package) => ['id' => $package['id'], 'data' => $package['data']],
                $packages
            )
        ];
    }

    public function getPackageVerdicts(): array
    {
        $packages = $this->getPackages();
        $vendorState = $this->getVendorState($packages);

        $response = $this->client->post(self::API_URL, [RequestOptions::JSON => $vendorState]);
        $verdicts = json_decode($response->getBody()->getContents(), true);

        $packageVerdicts = [];
        foreach ($packages as $package) {
            $packageVerdicts[] = new Verdict(
              $package['name'],
              $package['version'],
              $package['data'],
              $verdicts[$package['id']]['percentage'] ?? '-',
              $verdicts[$package['id']]['verdict'] ?? 'unknown'
            );
        }
        return $packageVerdicts;
    }
}