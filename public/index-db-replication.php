<?php

use HealthChecks\HealthCheckMonitor;
use HealthChecks\Service\DBReplicationCheck;

require '../vendor/autoload.php';

set_error_handler("customError", E_ALL);

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$dbCheck = new DBReplicationCheck();
$healthCheckMonitor = new HealthCheckMonitor($dbCheck);
$healthCheckMonitor->check()->send();