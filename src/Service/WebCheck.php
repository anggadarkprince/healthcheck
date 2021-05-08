<?php


namespace HealthChecks\Service;


use HealthChecks\Response\UncacheableResponse;

class WebCheck implements HealthCheck
{
    public function init()
    {

    }

    public function check()
    {
        $response = UncacheableResponse::create(['json' => true]);
        return $response
            ->setContent(json_encode([
                'status'  => 200,
                'message' => 'OK',
                'data'    => [
                    'host' => $_SERVER['HTTP_HOST'],
                    'web_server' => $_SERVER['SERVER_SOFTWARE'],
                ]
            ]))
            ->setStatusCode(200);
    }

    public function close()
    {

    }

    public function __toString()
    {
        return 'Web';
    }
}