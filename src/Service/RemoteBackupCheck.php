<?php

namespace HealthChecks\Service;

use Aws\S3\S3Client;
use HealthChecks\Response\UncacheableResponse;

class RemoteBackupCheck implements HealthCheck
{
    private S3Client $s3;

    private $directories = [];

    public function init()
    {
        $this->s3 = new S3Client([
            'version' => 'latest',
            'region' => $_ENV['S3_DEFAULT_REGION'],
            'credentials' => [
                'key' => $_ENV['S3_ACCESS_KEY_ID'],
                'secret' => $_ENV['S3_SECRET_ACCESS_KEY'],
            ],
            'endpoint' => $_ENV['S3_ENDPOINT'],
            'http' => [
                'verify' => false
            ]
        ]);
        $this->directories = json_decode($_ENV['S3_REMOTE_BACKUP_BUCKETS'] ?? '[]', true);
    }

    private function fetchListFolder($bucket, $prefixes, $append = true)
    {
        if (!array_is_list($prefixes)) {
            $prefixes = array_merge(...array_values($prefixes));
        }
        $allFolders = [];
        foreach ($prefixes as $prefix) {
            $result = $this->s3->listObjectsV2([
                'Bucket' => $bucket,
                'Prefix' => $prefix,
                'Delimiter' => '/',
            ]);

            $folders = [];
            if (isset($result['CommonPrefixes'])) {
                foreach ($result['CommonPrefixes'] as $subPrefix) {
                    $folders[] = $subPrefix['Prefix'];
                }
            }
            if ($append) {
                $allFolders[$prefix] = $folders;
            } else {
                $allFolders = array_merge($allFolders, $folders);
            }
        }
        return $allFolders;
    }

    public function check()
    {
        $directoryGroup = [];
        foreach ($this->directories as $label => $directory) {
            $bucket = $directory['bucket'];
            $path = $directory['path'];

            $yearFolders = $this->fetchListFolder($bucket, [$path]);
            $monthFolders = $this->fetchListFolder($bucket, $yearFolders);
            $dayFolders = $this->fetchListFolder($bucket, $monthFolders);

            $folderSizes = [];
            foreach ($dayFolders as $group => $dayGroups) {
                $folderSize = [
                    'group' => $group,
                    'paths' => [],
                ];
                foreach ($dayGroups as $folder) {
                    $size = 0;
                    $result = $this->s3->listObjectsV2([
                        'Bucket' => $bucket,
                        'Prefix' => $folder,
                    ]);

                    if (isset($result['Contents'])) {
                        foreach ($result['Contents'] as $object) {
                            $size += $object['Size'];
                        }
                    }

                    $folderSize['paths'][] = [
                        'path' => $folder,
                        'size' => $size,
                        'size_formatted' => format_bytes($size)
                    ];
                }
                usort($folderSize['paths'], function ($a, $b) {
                    return $b['path'] <=> $a['path'];
                });
                $folderSizes[] = $folderSize;
            }
            usort($folderSizes, function ($a, $b) {
                return $b['group'] <=> $a['group'];
            });
            $directoryGroup[$label] = $folderSizes;
        }

        $response = UncacheableResponse::create(['json' => true]);
        return $response
            ->setContent(json_encode([
                'status'  => 200,
                'message' => 'OK',
                'data'    => $directoryGroup
            ]))
            ->setStatusCode(200);
    }

    public function close()
    {

    }

    public function __toString()
    {
        return 'Remote Backup';
    }
}