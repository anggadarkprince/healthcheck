<?php

namespace HealthChecks\Service;

use HealthChecks\Response\UncacheableResponse;

class ServerCheck implements HealthCheck
{
    private $os;

    public function init()
    {
        $this->os = strtolower(PHP_OS);
    }

    public function check()
    {
        $response = UncacheableResponse::create(['json' => true]);
        return $response
            ->setContent(json_encode([
                'status' => 200,
                'message' => 'OK',
                'data' => [
                    'system' => $this->getSystemInfo(),
                    'memory' => $this->getMemoryInfo(),
                    'disk' => $this->getSystemDisk(),
                ]
            ]))
            ->setStatusCode(200);
    }

    private function getSystemDisk()
    {
        $diskTotal = disk_total_space('/');
        $diskFree = disk_free_space('/');
        $diskUse = round(100 - (($diskFree / $diskTotal) * 100)) . '%';

        $disk = [
            'total' => $diskTotal,
            'free' => $diskFree,
            'use' => $diskUse
        ];

        foreach ($disk as $key => $value) {
            if ($key != 'use') {
                if ($value < 1024) {
                    $disk[$key] = $value . ' B';
                } elseif ($value < 1048576) {
                    $disk[$key] = round($value / 1024, 2) . ' KB';
                } elseif ($value < 1048576000) {
                    $disk[$key] = round($value / 1048576, 2) . ' MB';
                } else {
                    $disk[$key] = round($value / 1048576000, 2) . ' GB';
                }
            }
        }

        return $disk;
    }

    private function getMemoryInfo()
    {
        $ram = [
            'total' => 0,
            'used' => 0,
            'available' => 0
        ];
        if (strpos($this->os, 'win') === false) {
            $data = explode("\n", shell_exec("free -m"));
            foreach ($data as $row) {
                if (strpos($row, 'Mem') !== false) {
                    $memories = explode(" ", preg_replace('/\s+/', ' ', $row));
                    $ram = [
                        'total' => $memories[1],
                        'used' => $memories[2],
                        'available' => end($memories)
                    ];
                }
            }
        }
        return $ram;
    }

    private function getSystemInfo()
    {
        if (strpos($this->os, 'win') !== false) {
            return [
                'Operating System' => 'Windows'
            ];
        } else {
            $data = explode("\n", shell_exec("hostnamectl"));
            $result = [];
            foreach ($data as $line) {
                if (!empty(trim($line))) {
                    list($key, $val) = explode(":", $line);
                    $result[trim($key)] = trim($val);
                }
            }
            return $result;
        }
    }

    public function close()
    {
        $this->os = null;
    }

    public function __toString()
    {
        return 'Server';
    }
}