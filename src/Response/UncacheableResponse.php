<?php

namespace HealthChecks\Response;

use Symfony\Component\HttpFoundation\Response;

class UncacheableResponse
{
    /**
     * Create non-cache response.
     *
     * @param array $options
     * @return Response
     */
    public static function create($options = [])
    {
        $response = new Response();

        // We set headers so it definitely does not cache
        $response->headers->set('Cache-Control', [
            'no-store',
            'no-cache',
            'must-revalidate',
            'max-age=0',
        ]);
        $response->headers->set('Cache-Control', [
            'post-check=0',
            'pre-check=0',
        ], false);
        $response->headers->set('Pragma', [
            'no-cache',
        ]);
        if ($options['json'] ?? false) {
            $response->headers->set('Content-Type', 'application/json');
        }

        return $response;
    }
}