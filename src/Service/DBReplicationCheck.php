<?php

namespace HealthChecks\Service;

use HealthChecks\Response\UncacheableResponse;
use mysqli;

class DBReplicationCheck implements HealthCheck
{
    private $conn;
    private $replicationResult;
    private $primaryResult;

    public function init()
    {
        $dbHost = getenv('DB_REPLICATION_HOST');
        $dbDatabase = getenv('DB_REPLICATION_DATABASE');
        $dbUsername = getenv('DB_REPLICATION_USERNAME');
        $dbPassword = getenv('DB_REPLICATION_PASSWORD');

        $this->conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbDatabase);
    }

    public function check()
    {
        $response = UncacheableResponse::create(['json' => true]);
        if ($this->conn->connect_error) {
            return $response
                ->setContent(json_encode([
                    'status'      => '500',
                    'message'     => 'Internal Server Error',
                    'description' => $this->conn->connect_error
                ]))
                ->setStatusCode(500);
        }

        $this->replicationResult = $this->conn->query("SELECT * FROM replication_group_members");

        $replicationStatus = $this->replicationResult->fetch_all(MYSQLI_ASSOC);
        $replicationRecovery = false;
        foreach ($replicationStatus as $row) {
            if ($row['MEMBER_STATE'] != 'ONLINE') {
                $replicationRecovery = true;
                break;
            }
        }

        $this->primaryResult = $this->conn->query("SHOW STATUS LIKE '%primary%'");
        $replicationPrimary = $this->primaryResult->fetch_assoc();

        if ($replicationRecovery) {
            return $response
                ->setContent(json_encode([
                    'status'      => '500',
                    'message'     => 'Internal Server Error',
                    'description' => 'Replication group member invalid',
                    'data' => [
                        'members' => $replicationStatus,
                        'primary' => $replicationPrimary
                    ]
                ]))
                ->setStatusCode(500);
        }

        return $response
            ->setContent(json_encode([
                'status'  => 200,
                'message' => 'OK',
                'data' => [
                    'host' => $this->conn->host_info,
                    'info' => $this->conn->server_info,
                    'version' => $this->conn->server_version,
                    'members' => $replicationStatus,
                    'primary' => $replicationPrimary
                ]
            ]))
            ->setStatusCode(200);
    }

    public function close()
    {
        mysqli_free_result($this->replicationResult);
        mysqli_free_result($this->primaryResult);
        mysqli_close($this->conn);
    }

    public function __toString()
    {
        return 'DB Replication';
    }
}