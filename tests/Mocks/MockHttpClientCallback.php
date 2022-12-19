<?php

namespace App\Tests\Mocks;

use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;
use ValueError;

class MockHttpClientCallback
{
    public function __invoke(
        string $method,
        string $url,
        array  $options = []
    ): ResponseInterface
    {
        return match ("$method $url") {
            'POST https://fyyn.api-us1.com/api/3/contacts' => new MockResponse(
                json_encode(
                    [
                        'contact' => [
                            'id' => 1,
                        ],
                    ]
                ),
                [
                    'http_code' => Response::HTTP_CREATED,
                ]
            ),

            default => throw new ValueError("Unexpected request: $method $url"),
        };
    }
}
