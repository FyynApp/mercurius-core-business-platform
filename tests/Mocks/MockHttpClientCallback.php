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
        echo ("\nMockHttpClientCallback: $method $url\n");

        return match ("$method $url") {
            'POST https://fyynio1671611137.api-us1.com/api/3/contacts' => new MockResponse(
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

            'POST https://fyynio1671611137.api-us1.com/api/3/contactTags' => new MockResponse(
                json_encode(
                    [
                        'contactTag' => [
                            'contact' => 1,
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
