<?php

namespace HealthChecks\Service;

use Aws\S3\S3Client;
use HealthChecks\Response\UncacheableResponse;

class ObjectStorageCheck implements HealthCheck
{
    private $s3;

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
    }

    public function check()
    {
        $response = UncacheableResponse::create(['json' => true]);

        $buckets = $this->s3->listBuckets();

        $bucketList = [];
        $totalUsage = 0;
        foreach ($buckets['Buckets'] as $bucket) {
            $iterator = $this->s3->getIterator('ListObjects', array(
                'Bucket' => $bucket['Name']
            ));

            $totalSize = 0;
            foreach ($iterator as $object) {
                $totalSize += $object['Size'];
            }
            $totalSizeUnit = $totalSize / 1024 / 1024;
            $totalSizeUnitName = 'MB';
            if ($totalSize > 1000000000) {
                $totalSizeUnit = $totalSizeUnit / 1024;
                $totalSizeUnitName = 'GB';
            }

            $totalUsage += $totalSize;

            $bucketList[] = [
                'bucket_name' => $bucket['Name'],
                'total' => $totalSizeUnit,
                'total_unit' => $totalSizeUnitName,
            ];
        }
        $totalUsageAll = $totalUsage / 1024 / 1024 / 1024;

        return $response
            ->setContent(json_encode([
                'status' => 200,
                'message' => 'OK',
                'data' => [
                    'endpoint' => $_ENV['S3_ENDPOINT'],
                    'region' => $_ENV['S3_DEFAULT_REGION'],
                    'reserved_space' => floatval($_ENV['S3_RESERVED_SPACE']),
                    'reserved_space_unit' => 'GB',
                    'owner' => [
                        'id' => $buckets['Owner']['ID'],
                        'name' => $buckets['Owner']['DisplayName'],
                    ],
                    'buckets' => $bucketList,
                    'total_usage' => $totalUsageAll,
                    'total_usage_unit' => 'GB',
                    'total_left' => $_ENV['S3_RESERVED_SPACE'] - $totalUsageAll,
                    'total_left_unit' => 'GB',
                    'usage_percent' => $totalUsageAll / $_ENV['S3_RESERVED_SPACE'] * 100,
                ]
            ]))
            ->setStatusCode(200);
    }

    public function close()
    {
        $this->s3 = null;
    }

    public function __toString()
    {
        return 'Object Storage';
    }
}