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
        $diskUse = number_format(100 - (($diskFree / $diskTotal) * 100), 1);

        $disk = [
            'total' => $diskTotal,
            'free' => $diskFree,
            'usage_percent' => $diskUse
        ];

        foreach ($disk as $key => $value) {
            if ($key != 'usage_percent') {
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

        $directoryReport = $_ENV['SERVER_DISK_DIRECTORY_SUMMARY'];
        if (!empty($directoryReport)) {
            if (strpos($this->os, 'win') !== false) {
                $disk['directory_report'] = [
                    'path' => $directoryReport,
                    'contents' => []
                ];
            } else {
                $directoryReportScan = rtrim($directoryReport, "/") . '/*';
                $data = explode("\n", shell_exec("du -hs {$directoryReportScan}"));
                $result = [];
                foreach ($data as $line) {
                    if (!empty(trim($line))) {
                        list($size, $path) = explode(" ", preg_replace('/\s+/', ' ', $line));
                        $dir = substr($path, strlen($directoryReport));
                        $result[trim($dir)] = trim($size);
                    }
                }
                $disk['directory_report'] = [
                    'path' => $directoryReport,
                    'contents' => $result,
                ];
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
                        'total' => number_format($memories[1] / 1000, 2) . 'GB',
                        'used' => number_format($memories[2] / 1000, 2) . 'GB',
                        'available' => number_format(end($memories) / 1000, 2) . 'GB'
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
                'operating_system' => 'Windows'
            ];
        } else {
            $data = explode("\n", shell_exec("hostnamectl"));
            $result = [];
            foreach ($data as $line) {
                if (!empty(trim($line))) {
                    list($key, $val) = explode(":", $line);
                    $result[str_replace(' ', '_', strtolower(trim($key)))] = trim($val);
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