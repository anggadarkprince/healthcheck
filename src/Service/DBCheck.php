<?php

namespace HealthChecks\Service;

use HealthChecks\Response\UncacheableResponse;
use mysqli;

class DBCheck implements HealthCheck
{
    private $conn;

    public function init()
    {
        $dbHost = $_ENV['DB_HOST'];
        $dbDatabase = $_ENV['DB_DATABASE'];
        $dbUsername = $_ENV['DB_USERNAME'];
        $dbPassword = $_ENV['DB_PASSWORD'];

        $this->conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbDatabase);
    }

    public function check()
    {
        $response = UncacheableResponse::create(['json' => true]);
        if ($this->conn->connect_error) {
            return $response
                ->setContent(json_encode([
                    'status'      => 500,
                    'message'     => 'Internal Server Error',
                    'description' => $this->conn->connect_error
                ]))
                ->setStatusCode(500);
        }
        return $response
            ->setContent(json_encode([
                'status'  => 200,
                'message' => 'OK',
                'data'    => [
                    'host' => $this->conn->host_info,
                    'info' => $this->conn->server_info,
                    'version' => $this->conn->server_version,
                ]
            ]))
            ->setStatusCode(200);
    }

    public function close()
    {
        mysqli_close($this->conn);
    }

    public function __toString()
    {
        return 'DB';
    }
}