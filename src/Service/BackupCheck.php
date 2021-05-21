<?php

namespace HealthChecks\Service;

use HealthChecks\Response\UncacheableResponse;

class BackupCheck implements HealthCheck
{
    public function init()
    {

    }

    public function check()
    {
        $directories = json_decode($_ENV['SERVER_BACKUP_DIRECTORY'] ?? '[]', true);

        $contents = [];
        foreach ($directories as $label => $directory) {
            $contents[$label] = [
                'backup' => $label,
                'location' => $directory,
                'data' => map_directory($directory['path'], $directory['depth'] ?? 1)
            ];
        }

        $response = UncacheableResponse::create(['json' => true]);
        return $response
            ->setContent(json_encode([
                'status'  => 200,
                'message' => 'OK',
                'data'    => $contents
            ]))
            ->setStatusCode(200);
    }

    public function close()
    {

    }

    public function __toString()
    {
        return 'Backup';
    }
}