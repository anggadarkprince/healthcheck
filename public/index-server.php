<?php

use HealthChecks\HealthCheckMonitor;
use HealthChecks\Service\ServerCheck;

require '../vendor/autoload.php';

set_error_handler("customError", E_ALL);

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$serverCheck = new ServerCheck();
$healthCheckMonitor = new HealthCheckMonitor($serverCheck);
$healthCheckMonitor->send();