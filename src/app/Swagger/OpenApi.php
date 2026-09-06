<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Ehkam API',
    description: 'API documentation for Ehkam Quran and Islamic Sciences Management System'
)]
#[OA\Server(
    url: '/',
    description: 'Ehkam API Server'
)]
#[OA\Get(
    path: '/api/test-swagger',
    summary: 'Test Swagger',
    description: 'Test endpoint to verify that Swagger documentation generation is working.',
    tags: ['System'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Swagger is working'
        )
    ]
)]
class OpenApi
{
}
