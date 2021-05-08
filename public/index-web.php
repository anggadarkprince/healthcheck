<?php

use HealthChecks\HealthCheckMonitor;
use HealthChecks\Service\WebCheck;

require '../vendor/autoload.php';

set_error_handler("customError", E_ALL);

$dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

$webCheck = new WebCheck();
$healthCheckMonitor = new HealthCheckMonitor($webCheck);
$healthCheckMonitor->check()->send();