<?php


namespace HealthChecks\Service;


interface HealthCheck
{
    public function init();
    public function check();
    public function close();
}