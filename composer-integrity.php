<?php

use DI\Container;
use Sansec\Integrity\IntegrityCommand;
use Sansec\Integrity\PackageResolver\LockReaderStrategy;
use Symfony\Component\Console\Application;

require_once __DIR __ . '/vendor/autoload.php';

$container = new Container();

$application = $container->get(Application::class);
$application->add($container->make(IntegrityCommand::class, [
    'packageResolverStrategy' => $container->make(
        LockReaderStrategy::class,
        ['rootDirectory' => \getcwd()]
    )
]));
$application->setDefaultCommand('integrity');
$application->run();
