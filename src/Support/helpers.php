<?php

use HealthChecks\Response\UncacheableResponse;

if ( ! function_exists('customError')) {

    function customError($errorNo, $errorString)
    {
        $response = UncacheableResponse::create();

        $return = [
            'status'      => 500,
            'message'     => 'Internal Server Error',
            'description' => "[{$errorNo}] {$errorString}"
        ];

        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($return));
        $response->setStatusCode(500);

        $response->send();
        die();
    }

}

if ( ! function_exists('map_directory')) {

    function map_directory($source_dir, $directory_depth = 0, $hidden = FALSE)
    {
        if ($fp = @opendir($source_dir)) {
            $fileData = array();
            $new_depth = $directory_depth - 1;
            $source_dir = rtrim($source_dir, '/') . '/';

            while (FALSE !== ($file = readdir($fp))) {
                // Remove '.', '..', and hidden files [optional]
                if (!trim($file, '.') or ($hidden == FALSE && $file[0] == '.')) {
                    continue;
                }

                if (($directory_depth < 1 or $new_depth > 0) && @is_dir($source_dir . $file)) {
                    $fileData[] = [
                        'name' => $file,
                        'is_dir' => true,
                        'date_modified' => date ("F d Y H:i:s.", filemtime($source_dir . $file)),
                        'contents' => map_directory($source_dir . $file . '/', $new_depth, $hidden),
                    ];
                } else {
                    $fileData[] = [
                        'name' => $file,
                        'is_dir' => @is_dir($source_dir . $file),
                        'date_modified' => date ("F d Y H:i:s.", filemtime($source_dir . $file))
                    ];
                }
            }

            closedir($fp);
            return $fileData;
        }
        return FALSE;
    }
}

if (!function_exists('print_debug')) {
    function print_debug($data, $dieImmediately = true)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        if ($dieImmediately) die();
    }
}

if (!function_exists('format_bytes')) {
    function format_bytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . $units[$pow];
    }
}