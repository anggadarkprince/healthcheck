<?php

namespace HealthChecks;

use HealthChecks\Response\UncacheableResponse;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\Response;

class HealthCheckMonitor
{
    private $healthCheck;

    /**
     * HealthCheckMonitor constructor.
     *
     * @param $healthCheck
     */
    public function __construct($healthCheck = null)
    {
        $this->healthCheck = $healthCheck;
    }

    /**
     * @param mixed $healthCheck
     */
    public function setHealthCheck($healthCheck)
    {
        $this->healthCheck = $healthCheck;
    }

    /**
     * @return mixed
     */
    public function getHealthCheck()
    {
        return $this->healthCheck;
    }

    /**
     * Invoke HealthCheck object.
     */
    public function check()
    {
        try {
            $reflection = new ReflectionClass($this->healthCheck);
            $result = null;

            if ($reflection->hasMethod('init')) {
                $reflection->getMethod('init')->invoke($this->healthCheck);
            }

            if ($reflection->hasMethod('check')) {
                $result = $reflection->getMethod('check')->invoke($this->healthCheck);
            } else {
                throw new ReflectionException();
            }

            if ($reflection->hasMethod('close')) {
                $reflection->getMethod('close')->invoke($this->healthCheck);
            }

            return $result;
        } catch (ReflectionException $e) {
        }
        return UncacheableResponse::create(['json' => true])
            ->setContent(json_encode([
                'status'      => 500,
                'message'     => 'Internal Server Error',
                'description' => "Invalid internal implementation"
            ]))
            ->setStatusCode(500);
    }

    /**
     * Check and return health monitoring.
     *
     * @return Response
     */
    public function send()
    {
        return $this->check()->send();
    }
}