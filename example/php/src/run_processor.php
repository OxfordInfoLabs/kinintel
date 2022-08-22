<?php

// Ensure autoloader run from vendor.
use Kiniauth\Services\Security\AuthenticationService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Core\Bootstrapper;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Init;
use Kinintel\Services\DataProcessor\DataProcessorService;

include_once "../vendor/autoload.php";

// Ensure basic initialisation has occurred.
Container::instance()->get(Init::class);
Container::instance()->get(Bootstrapper::class);

/**
 * @var AuthenticationService $authenticationService
 */
AuthenticationHelper::login("admin@kinicart.com", "password");

$processorKey = $argv[1];

$dataProcessorService = Container::instance()->get(DataProcessorService::class);
$dataProcessorService->processDataProcessorInstance($processorKey);
