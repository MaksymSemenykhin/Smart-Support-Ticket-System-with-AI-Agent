<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'API-only backend for support tickets with async AI enrichment.',
    title: 'Smart Support Ticket API'
)]
#[OA\Server(
    url: '/',
    description: 'Local / Docker'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    bearerFormat: 'Token',
    scheme: 'bearer'
)]
final class OpenApiSpec
{
}
