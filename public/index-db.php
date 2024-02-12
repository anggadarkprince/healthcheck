<?php

use HealthChecks\HealthCheckMonitor;
use HealthChecks\Service\DBCheck;

require '../vendor/autoload.php';

set_error_handler("customError", E_ALL);

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$dbCheck = new DBCheck();
$healthCheckMonitor = new HealthCheckMonitor($dbCheck);
$healthCheckMonitor->check()->send();