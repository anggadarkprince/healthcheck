<?php

use HealthChecks\HealthCheckMonitor;
use HealthChecks\Service\RemoteBackupCheck;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$remoteBackupCheck = new RemoteBackupCheck();
$remoteBackupCheckMonitor = new HealthCheckMonitor($remoteBackupCheck);
$remoteBackupCheckMonitor->send();