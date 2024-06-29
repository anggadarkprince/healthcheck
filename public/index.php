<?php

use HealthChecks\HealthCheckMonitor;
use HealthChecks\Response\UncacheableResponse;
use HealthChecks\Service\BackupCheck;
use HealthChecks\Service\DBCheck;
use HealthChecks\Service\DBReplicationCheck;
use HealthChecks\Service\ObjectStorageCheck;
use HealthChecks\Service\RemoteBackupCheck;
use HealthChecks\Service\ServerCheck;
use HealthChecks\Service\WebCheck;

require '../vendor/autoload.php';

//set_error_handler("customError", E_ALL);

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$services = [
    new WebCheck(),
    new DBCheck(),
    new DBReplicationCheck(),
    new ObjectStorageCheck(),
    new ServerCheck(),
    new BackupCheck(),
    new RemoteBackupCheck(),
];
$healthCheckMonitor = new HealthCheckMonitor();

$results = [];
foreach ($services as $service) {
    $healthCheckMonitor->setHealthCheck($service);
    $result = $healthCheckMonitor->check();
    $results[] = [
        'service_name' => (string) $service,
        'health_check' => json_decode($result->getContent(), true)
    ];
}
UncacheableResponse::create(['json' => true])
    ->setContent(json_encode([
        'time' => time(),
        'total_service' => count($results),
        'services' => $results
    ]))
    ->setStatusCode(200)
    ->send();