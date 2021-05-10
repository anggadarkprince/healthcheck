<?php

use HealthChecks\HealthCheckMonitor;
use HealthChecks\Service\DBCheck;
use HealthChecks\Service\ObjectStorageCheck;

require '../vendor/autoload.php';

set_error_handler("customError", E_ALL);

$dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

$objectStorageCheck = new ObjectStorageCheck();
$healthCheckMonitor = new HealthCheckMonitor($objectStorageCheck);
$healthCheckMonitor->check()->send();