<?php

use HealthChecks\HealthCheckMonitor;
use HealthChecks\Service\BackupCheck;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$backupCheck = new BackupCheck();
$backupCheckMonitor = new HealthCheckMonitor($backupCheck);
$backupCheckMonitor->send();